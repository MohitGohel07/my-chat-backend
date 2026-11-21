<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StreamLogController extends Controller
{
    /**
     * Display structured stream events and system logs.
     */
    public function index(Request $request, ?string $streamId = null): View
    {
        $filterStreamId = $streamId ?: $request->query('stream_id');

        $query = ClipperLog::with('stream')->latest();

        if ($filterStreamId) {
            $query->where('stream_id', $filterStreamId);
        }

        $logs = $query->take(100)->get();

        return view('clipper.logs.index', compact('logs', 'filterStreamId'));
    }
}
