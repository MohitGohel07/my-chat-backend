<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
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
        $logs = []; // Scaffolding empty list for Phase 1

        return view('clipper.logs.index', compact('logs', 'filterStreamId'));
    }
}

