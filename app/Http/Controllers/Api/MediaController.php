<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required_without:image_base64', 'file', 'max:51200'], // max 50MB for video/image
            'image_base64' => ['required_without:file', 'string'],
        ]);

        $uploadDir = public_path('uploads');
        if (! file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension() ?: 'bin';
            $filename = Str::random(32) . '.' . $extension;
            $file->move($uploadDir, $filename);

            $url = url('uploads/' . $filename);

            return response()->json([
                'url' => $url,
                'filename' => $filename,
                'extension' => $extension,
                'size' => filesize($uploadDir . DIRECTORY_SEPARATOR . $filename),
            ], 201);
        }

        if ($request->filled('image_base64')) {
            $base64 = $request->input('image_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
                $type = strtolower($type[1]);
            } else {
                $type = 'jpg';
            }

            $decoded = base64_decode($base64);
            $filename = Str::random(32) . '.' . $type;
            file_put_contents($uploadDir . DIRECTORY_SEPARATOR . $filename, $decoded);

            $url = url('uploads/' . $filename);

            return response()->json([
                'url' => $url,
                'filename' => $filename,
                'extension' => $type,
                'size' => strlen($decoded),
            ], 201);
        }

        return response()->json(['message' => 'Upload failed.'], 422);
    }
}

