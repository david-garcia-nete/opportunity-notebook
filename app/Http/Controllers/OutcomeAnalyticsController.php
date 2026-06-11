<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Services\OutcomeAnalyticsService;
use App\Services\OpportunityReadinessService;
use Illuminate\View\View;

class OutcomeAnalyticsController extends Controller
{
    public function __invoke(OutcomeAnalyticsService $analytics, OpportunityReadinessService $readiness): View
    {
        return view('outcome-analytics.index', [
            'breakdowns' => $analytics->breakdowns(),
            'outcomes' => Opportunity::OUTCOMES,
            'recentOutcomes' => $analytics->recentOutcomes(),
            'readiness' => $readiness,
            'summary' => $analytics->summary(),
            'preference' => request()->user()?->preference,
        ]);
    }
}
