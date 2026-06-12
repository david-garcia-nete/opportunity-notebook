<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Services\OpportunityReviewSummaryService;
use Illuminate\View\View;

class OpportunityReviewSummaryController extends Controller
{
    public function __invoke(Opportunity $opportunity, OpportunityReviewSummaryService $summary): View
    {
        return view('opportunities.review-summary', [
            'opportunity' => $opportunity,
            'summary' => $summary->summarize($opportunity),
        ]);
    }
}
