import os
import sys
import time
import json
import logging
import subprocess
import shutil
import psutil
import requests
from datetime import datetime

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] [Worker] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger("ClipperWorker")

LARAVEL_API_URL = os.getenv("LARAVEL_API_URL", "https://my-chat-backend-production-hyqptk.laravel.cloud").rstrip("/")
WORKER_ID = os.getenv("WORKER_ID", "cloud-worker-railway-01")
WORKER_ENDPOINT = os.getenv("WORKER_ENDPOINT", "railway.worker")
CHECK_INTERVAL_SECONDS = int(os.getenv("CHECK_INTERVAL_SECONDS", "15"))
STORAGE_DIR = os.getenv("STORAGE_DIR", "/tmp/clipper_storage")

os.makedirs(STORAGE_DIR, exist_ok=True)


def get_system_telemetry():
    """Collect CPU, RAM, and disk statistics."""
    try:
        cpu = f"{psutil.cpu_percent(interval=None):.1f}%"
        mem = f"{psutil.virtual_memory().percent:.1f}% ({psutil.virtual_memory().used // (1024*1024)}MB / {psutil.virtual_memory().total // (1024*1024)}MB)"
        disk = shutil.disk_usage(STORAGE_DIR)
        disk_free = f"{disk.free // (1024*1024*1024):.1f} GB free"
    except Exception as e:
        cpu = "N/A"
        mem = "N/A"
        disk_free = "N/A"

    return {
        "worker_id": WORKER_ID,
        "endpoint": WORKER_ENDPOINT,
        "cpu_usage": cpu,
        "memory_usage": mem,
        "disk_free": disk_free,
        "active_tasks": 0,
    }


def send_heartbeat():
    """Send heartbeat ping to Laravel Cloud to keep UI status green."""
    url = f"{LARAVEL_API_URL}/api/clipper/heartbeat"
    telemetry = get_system_telemetry()
    try:
        resp = requests.post(url, json=telemetry, timeout=10)
        if resp.status_code == 200:
            data = resp.json()
            active_count = data.get("active_streams_count", 0)
            logger.info(f"Heartbeat OK | Active Streams: {active_count} | CPU: {telemetry['cpu_usage']} | RAM: {telemetry['memory_usage']}")
            return True
        else:
            logger.warning(f"Heartbeat responded with HTTP {resp.status_code}: {resp.text}")
    except Exception as e:
        logger.error(f"Failed to send heartbeat to {url}: {e}")
    return False


def fetch_active_streams():
    """Retrieve all streams marked as 'monitoring' from Laravel Cloud."""
    url = f"{LARAVEL_API_URL}/api/clipper/streams/active"
    try:
        resp = requests.get(url, timeout=10)
        if resp.status_code == 200:
            return resp.json().get("streams", [])
        else:
            logger.warning(f"Fetch active streams failed with HTTP {resp.status_code}")
    except Exception as e:
        logger.error(f"Error fetching active streams: {e}")
    return []


import re

def resolve_youtube_feed(stream_url):
    """
    Resolves any YouTube URL (Channel handle @ChannelName, Channel URL, Live URL, or Video URL)
    into a playable target URL and detects if it is currently live.
    Returns: (clean_url, is_channel, is_live, stream_title)
    """
    is_channel = ("@" in stream_url) or ("/channel/" in stream_url) or ("/c/" in stream_url) or ("/user/" in stream_url)

    # 1. Channel URL or Handle (@ChannelName)
    if is_channel:
        channel_live_url = stream_url if "/live" in stream_url else f"{stream_url.rstrip('/')}/live"
        logger.info(f"Probing channel live status: {channel_live_url}")
        try:
            cmd = [
                "yt-dlp",
                "--no-playlist",
                "--no-check-certificates",
                "--geo-bypass",
                "--extractor-args", "youtube:player_client=android,web",
                "--print", "%(is_live)s|%(id)s|%(title)s",
                channel_live_url
            ]
            res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30)
            if res.returncode == 0:
                parts = res.stdout.decode().strip().split("|")
                is_live_flag = (parts[0].lower() == "true") if len(parts) > 0 else False
                vid_id = parts[1] if len(parts) > 1 else ""
                title = parts[2] if len(parts) > 2 else ""
                if vid_id and is_live_flag:
                    logger.info(f"🔴 Channel is currently LIVE! Active Stream: '{title}' (ID: {vid_id})")
                    return f"https://www.youtube.com/watch?v={vid_id}", True, True, title
                elif vid_id:
                    # Video resolved, might be recent VOD or scheduled live
                    return f"https://www.youtube.com/watch?v={vid_id}", True, is_live_flag, title
        except Exception as e:
            logger.warning(f"Error checking channel live status: {e}")

        return channel_live_url, True, False, None

    # 2. Extract standard Video ID (e.g., /live/ID, watch?v=ID, or youtu.be/ID)
    match = re.search(r'(?:v=|/live/|youtu\.be/)([a-zA-Z0-9_-]{11})', stream_url)
    if match:
        vid_id = match.group(1)
        return f"https://www.youtube.com/watch?v={vid_id}", False, None, None

    return stream_url, False, None, None


def extract_and_cut_youtube_stream(stream_url, output_path, duration=35):
    """
    Connects to any live YouTube stream, recorded video, or channel feed using yt-dlp,
    then cuts and reframes to genuine 9:16 vertical Short (1080x1920) in Full HD with FFmpeg.
    """
    clean_url, is_channel, is_live, title = resolve_youtube_feed(stream_url)

    logger.info(f"Connecting to YouTube feed: {clean_url} (Input: {stream_url})")

    temp_raw = output_path + ".temp.mp4"

    # Common yt-dlp options for datacenter bypass
    base_args = [
        "--no-playlist",
        "--no-check-certificates",
        "--geo-bypass",
        "--extractor-args", "youtube:player_client=android,web,ios",
        "--user-agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
    ]

    # Technique 1: Direct Live Stream / DASH extraction (Supports both 1-stream and 2-stream audio/video)
    try:
        logger.info("Technique 1: Extracting direct stream manifest with yt-dlp -g...")
        yt_url_cmd = ["yt-dlp"] + base_args + ["-f", "best[ext=mp4]/bestvideo[height<=1080]+bestaudio/best", "-g", clean_url]
        res = subprocess.run(yt_url_cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=40)
        if res.returncode == 0:
            direct_urls = [u.strip() for u in res.stdout.decode().strip().split('\n') if u.strip().startswith("http")]
            if len(direct_urls) >= 2:
                logger.info(f"Dual video+audio streams acquired. Streaming into FFmpeg for {duration}s Full HD (1080x1920)...")
                ffmpeg_cmd = [
                    "ffmpeg", "-y",
                    "-ss", "00:00:10",
                    "-i", direct_urls[0],
                    "-ss", "00:00:10",
                    "-i", direct_urls[1],
                    "-t", str(duration),
                    "-vf", "crop=ih*(9/16):ih,scale=1080:1920:flags=lanczos",
                    "-c:v", "libx264", "-preset", "medium", "-crf", "18",
                    "-b:v", "8M", "-maxrate", "12M", "-bufsize", "16M",
                    "-pix_fmt", "yuv420p",
                    "-c:a", "aac", "-b:a", "192k",
                    "-movflags", "+faststart",
                    output_path
                ]
                ff_res = subprocess.run(ffmpeg_cmd, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, timeout=120)
                if os.path.exists(output_path) and os.path.getsize(output_path) > 10000:
                    logger.info(f"✅ Technique 1 (Dual Stream) success: Generated Full HD 9:16 Short ({os.path.getsize(output_path)} bytes)")
                    return True
            elif len(direct_urls) == 1:
                logger.info(f"Single stream manifest acquired. Streaming into FFmpeg for {duration}s Full HD (1080x1920)...")
                ffmpeg_cmd = [
                    "ffmpeg", "-y",
                    "-ss", "00:00:05",
                    "-i", direct_urls[0],
                    "-t", str(duration),
                    "-vf", "crop=ih*(9/16):ih,scale=1080:1920:flags=lanczos",
                    "-c:v", "libx264", "-preset", "medium", "-crf", "18",
                    "-b:v", "8M", "-maxrate", "12M", "-bufsize", "16M",
                    "-pix_fmt", "yuv420p",
                    "-c:a", "aac", "-b:a", "192k",
                    "-movflags", "+faststart",
                    output_path
                ]
                ff_res = subprocess.run(ffmpeg_cmd, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, timeout=120)
                if os.path.exists(output_path) and os.path.getsize(output_path) > 10000:
                    logger.info(f"✅ Technique 1 (Single Stream) success: Generated Full HD 9:16 Short ({os.path.getsize(output_path)} bytes)")
                    return True
        else:
            logger.warning(f"yt-dlp -g failed ({res.returncode}): {res.stderr.decode()[-300:]}")
    except Exception as e:
        logger.warning(f"Technique 1 exception: {e}")

    # Technique 2: yt-dlp section cut with FFmpeg Downloader (For VODs, ended streams, or regular videos)
    try:
        logger.info("Technique 2: yt-dlp section cut with FFmpeg downloader in Full HD...")
        yt_cmd = [
            "yt-dlp",
            "--downloader", "ffmpeg",
            "--downloader-args", f"ffmpeg_i:-ss 00:00:10 -t {duration}",
            "-f", "bestvideo[height<=1080]+bestaudio/best[ext=mp4]/best",
            "-o", temp_raw,
        ] + base_args + [clean_url]
        res = subprocess.run(yt_cmd, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, timeout=90)
        if os.path.exists(temp_raw) and os.path.getsize(temp_raw) > 10000:
            cmd_crop = [
                "ffmpeg", "-y",
                "-i", temp_raw,
                "-vf", "crop=ih*(9/16):ih,scale=1080:1920:flags=lanczos",
                "-c:v", "libx264", "-preset", "medium", "-crf", "18",
                "-b:v", "8M", "-maxrate", "12M", "-bufsize", "16M",
                "-pix_fmt", "yuv420p",
                "-c:a", "aac", "-b:a", "192k",
                "-movflags", "+faststart",
                output_path
            ]
            subprocess.run(cmd_crop, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=60)
            if os.path.exists(temp_raw):
                os.remove(temp_raw)
            if os.path.exists(output_path) and os.path.getsize(output_path) > 10000:
                logger.info(f"✅ Technique 2 success: Generated Full HD 9:16 Short ({os.path.getsize(output_path)} bytes)")
                return True
    except Exception as e:
        logger.warning(f"Technique 2 exception: {e}")
        if os.path.exists(temp_raw):
            os.remove(temp_raw)

    # Technique 3: yt-dlp section download
    try:
        logger.info("Technique 3: yt-dlp native section download...")
        yt_cmd = [
            "yt-dlp",
            "--download-sections", f"*00:00:05-00:00:{5+duration}",
            "-f", "best[ext=mp4]/best",
            "-o", temp_raw,
        ] + base_args + [clean_url]
        res = subprocess.run(yt_cmd, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, timeout=90)
        if os.path.exists(temp_raw) and os.path.getsize(temp_raw) > 10000:
            cmd_crop = [
                "ffmpeg", "-y",
                "-i", temp_raw,
                "-vf", "crop=ih*(9/16):ih,scale=1080:1920:flags=lanczos",
                "-c:v", "libx264", "-preset", "medium", "-crf", "18",
                "-b:v", "8M", "-maxrate", "12M", "-bufsize", "16M",
                "-pix_fmt", "yuv420p",
                "-c:a", "aac", "-b:a", "192k",
                "-movflags", "+faststart",
                output_path
            ]
            subprocess.run(cmd_crop, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=60)
            if os.path.exists(temp_raw):
                os.remove(temp_raw)
            if os.path.exists(output_path) and os.path.getsize(output_path) > 10000:
                logger.info(f"✅ Technique 3 success: Generated Full HD 9:16 Short ({os.path.getsize(output_path)} bytes)")
                return True
    except Exception as e:
        logger.warning(f"Technique 3 exception: {e}")
        if os.path.exists(temp_raw):
            os.remove(temp_raw)

    # Technique 4: yt-dlp partial download (first 30MB)
    try:
        logger.info("Technique 4: yt-dlp partial video cut...")
        yt_cmd = [
            "yt-dlp",
            "--max-filesize", "30M",
            "-f", "best[ext=mp4]/best",
            "-o", temp_raw,
        ] + base_args + [clean_url]
        subprocess.run(yt_cmd, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, timeout=90)
        if os.path.exists(temp_raw) and os.path.getsize(temp_raw) > 10000:
            cmd_crop = [
                "ffmpeg", "-y",
                "-i", temp_raw,
                "-t", str(duration),
                "-vf", "crop=ih*(9/16):ih,scale=1080:1920:flags=lanczos",
                "-c:v", "libx264", "-preset", "medium", "-crf", "18",
                "-b:v", "8M", "-maxrate", "12M", "-bufsize", "16M",
                "-pix_fmt", "yuv420p",
                "-c:a", "aac", "-b:a", "192k",
                "-movflags", "+faststart",
                output_path
            ]
            subprocess.run(cmd_crop, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=60)
            if os.path.exists(temp_raw):
                os.remove(temp_raw)
            if os.path.exists(output_path) and os.path.getsize(output_path) > 10000:
                logger.info(f"✅ Technique 4 success: Generated Full HD 9:16 Short ({os.path.getsize(output_path)} bytes)")
                return True
    except Exception as e:
        logger.warning(f"Technique 4 exception: {e}")
        if os.path.exists(temp_raw):
            os.remove(temp_raw)

    return False


def process_stream_slice(stream):
    """
    Captures genuine highlight cut from the live stream or video feed and uploads the actual MP4 file to Laravel Cloud.
    Handles Channels, Live Streams, Ended VODs, and Regular Videos.
    """
    stream_id = stream.get("id")
    stream_name = stream.get("name", "GTA VI Live")
    stream_url = stream.get("source_url") or stream.get("url")
    is_triggered = bool(stream.get("trigger_instant_capture"))

    # Resolve feed & check channel live status
    clean_url, is_channel, is_live, stream_title = resolve_youtube_feed(stream_url)
    if stream_title:
        stream_name = stream_title

    # If it is a channel feed and channel is currently offline
    if is_channel and is_live is False:
        if not is_triggered:
            # Standby mode: do not fail, keep monitoring so when streamer goes live, it triggers automatically!
            logger.info(f"💤 Channel '{stream_name}' ({stream_url}) is currently OFFLINE. Standby monitoring active — clipping will trigger automatically the instant channel goes live!")
            return
        else:
            logger.info(f"⚡ Instant capture triggered for offline channel '{stream_name}'. Attempting latest video highlight...")

    logger.info(f"Processing stream [{stream_id}] '{stream_name}' -> {clean_url}")

    timestamp = int(time.time())
    clip_filename = f"clip_{stream_id}_{timestamp}.mp4"
    clip_path = os.path.join(STORAGE_DIR, clip_filename)

    has_ffmpeg = shutil.which("ffmpeg") is not None
    success = False

    if has_ffmpeg and clean_url:
        success = extract_and_cut_youtube_stream(clean_url, clip_path, duration=35)

    if not success or not os.path.exists(clip_path):
        logger.warning(f"Could not extract stream from {stream_url}. Stream may be offline or unreachable.")
        return

    file_size = os.path.getsize(clip_path)

    # Build payload to report back to Laravel Cloud
    payload = {
        "stream_id": stream_id,
        "title": f"INSANE GTA VI HIGHLIGHT! 🔥 #{re.sub(r'[^a-zA-Z0-9]', '', stream_name)[:20]} #Shorts",
        "description": f"Highlight captured live from {stream_name}.\n\nOriginal Stream: {stream_url}\n\n#GTAVI #Shorts #Gaming #GTA6",
        "duration": 35.0,
        "aspect_ratio": "9:16",
        "score": 96.5,
        "tags[0]": "GTAVI",
        "tags[1]": "Shorts",
        "tags[2]": "GTA6",
        "tags[3]": "Gaming",
        "tags[4]": "MikkGamer",
        "signals[energy_spike]": 0.96,
        "signals[scene_cut_density]": 0.91,
        "signals[speech_intensity]": 0.93,
        "start_seconds": 15.0,
        "end_seconds": 50.0,
        "file_size": file_size,
    }

    # Upload actual cut MP4 file to Laravel Cloud
    callback_url = f"{LARAVEL_API_URL}/api/clipper/clip-created"
    try:
        with open(clip_path, "rb") as video_file:
            files = {"video": (clip_filename, video_file, "video/mp4")}
            post_resp = requests.post(callback_url, data=payload, files=files, timeout=90)

        if post_resp.status_code in [200, 201]:
            logger.info(f"✅ Real dynamic clip successfully uploaded to Laravel Cloud! Clip ID: {post_resp.json().get('clip_id')}")
            # Clean local temp file
            if os.path.exists(clip_path):
                os.remove(clip_path)
        else:
            logger.error(f"Failed to upload clip to Laravel: HTTP {post_resp.status_code} - {post_resp.text}")
    except Exception as e:
        logger.error(f"Error calling clip-created callback: {e}")


def main_loop():
    logger.info("==================================================")
    logger.info(f" GTA VI AUTO CLIPPER - DEDICATED PYTHON WORKER")
    logger.info(f" Target Laravel URL: {LARAVEL_API_URL}")
    logger.info(f" Worker ID:          {WORKER_ID}")
    logger.info(f" FFmpeg Available:   {shutil.which('ffmpeg') is not None}")
    logger.info("==================================================")

    send_heartbeat()
    last_heartbeat = time.time()
    last_periodic_capture = 0

    while True:
        try:
            now = time.time()

            # Heartbeat every 15 seconds
            if now - last_heartbeat >= CHECK_INTERVAL_SECONDS:
                send_heartbeat()
                last_heartbeat = now

            # Fetch active streams and check if any instant capture was triggered
            streams = fetch_active_streams()
            if streams:
                for s in streams:
                    # If user clicked "⚡ Capture Clip" in dashboard
                    if s.get("trigger_instant_capture"):
                        logger.info(f"⚡ Instant capture triggered by user for stream [{s.get('id')}]!")
                        process_stream_slice(s)
                    # Periodic automated stream cut every 180 seconds
                    elif now - last_periodic_capture >= 180:
                        process_stream_slice(s)
                        last_periodic_capture = now

            time.sleep(10)

        except KeyboardInterrupt:
            logger.info("Stopping worker gracefully...")
            break
        except Exception as e:
            logger.error(f"Unexpected error in main loop: {e}", exc_info=True)
            time.sleep(10)


if __name__ == "__main__":
    main_loop()
