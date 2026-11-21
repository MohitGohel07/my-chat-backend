# GTA VI Auto Clipper — Dedicated Video Worker Service 🌴

Dedicated background worker that captures live streams, detects highlight moments (audio spikes & action cuts), renders 9:16 vertical Shorts using FFmpeg, and reports clips to the Laravel Cloud orchestrator.

---

## 🚀 Option 1: 1-Click Cloud Deployment (Railway.app / Render) — *Recommended for 24/7 Operation*

With cloud deployment, your worker runs 24/7 without needing your laptop to be on.

### A. Deploy on Railway.app (Free & Fast)
1. Go to [railway.app](https://railway.app) and log in with your GitHub account.
2. Click **+ New Project** &rarr; **Deploy from GitHub repo**.
3. Select this repository: `MohitGohel07/my-chat-backend`.
4. In the project settings:
   - **Dockerfile Path**: `worker/Dockerfile`
   - **Environment Variables**:
     - `LARAVEL_API_URL` = `https://my-chat-backend-production-hyqptk.laravel.cloud`
     - `WORKER_ID` = `cloud-worker-railway-01`
5. Click **Deploy**.
6. Within 60 seconds, your Laravel Cloud Dashboard at `https://my-chat-backend-production-hyqptk.laravel.cloud/clipper/health` will turn **🟢 Worker Online**!

---

### B. Deploy on Render.com (Free)
1. Go to [render.com](https://render.com) and log in.
2. Click **New +** &rarr; **Background Worker**.
3. Connect `MohitGohel07/my-chat-backend`.
4. Configure:
   - **Runtime**: `Docker`
   - **Dockerfile Path**: `worker/Dockerfile`
   - **Environment Variable**: `LARAVEL_API_URL` = `https://my-chat-backend-production-hyqptk.laravel.cloud`
5. Click **Create Background Worker**.

---

## 💻 Option 2: Run Locally on Your PC / Laptop

If you want to run it locally:
```bash
# 1. Navigate to worker directory
cd worker

# 2. Install dependencies
pip install -r requirements.txt

# 3. Set the Laravel Cloud API URL
# Windows PowerShell:
$env:LARAVEL_API_URL="https://my-chat-backend-production-hyqptk.laravel.cloud"

# 4. Start the worker
python main.py
```
*Note: Local running requires Python 3.11+ and FFmpeg installed on your machine.*

---

## 📡 API Endpoints Handled by Laravel Cloud
- `POST /api/clipper/heartbeat` &rarr; Telemetry & alive signal (CPU, RAM, disk)
- `GET /api/clipper/streams/active` &rarr; List of streams with `status = monitoring`
- `POST /api/clipper/clip-created` &rarr; Ingestion of newly generated vertical clips

