<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Services\OpportunityStrategicContextService;
use Illuminate\View\View;

class OpportunityStrategicContextController extends Controller
{
    public function __invoke(Opportunity $opportunity, OpportunityStrategicContextService $context): View
    {
        return view('opportunities.strategic-context', [
            'opportunity' => $opportunity,
            'context' => $context->build($opportunity),
        ]);
    }
}
