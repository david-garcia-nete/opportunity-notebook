<?php

namespace App\Http\Controllers;

use App\Services\OpportunityTimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimelineController extends Controller
{
    public function __invoke(Request $request, OpportunityTimelineService $timeline): View
    {
        $focusOnly = $request->boolean('focus');

        return view('timeline.index', [
            'focusOnly' => $focusOnly,
            'timeline' => $timeline->global($focusOnly),
        ]);
    }
}
