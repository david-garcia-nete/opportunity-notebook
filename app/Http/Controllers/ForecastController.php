<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Services\OpportunityForecastService;
use App\Support\Statuses;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForecastController extends Controller
{
    public function __invoke(Request $request, OpportunityForecastService $forecast): View
    {
        $preference = $request->user()?->preference;
        $opportunities = Opportunity::query()
            ->whereNotIn('status', Statuses::terminalOpportunities())
            ->with([
                'actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
                'opportunityGaps.actions',
                'projects',
            ])
            ->get();

        $summaries = $forecast->summaries($opportunities, $preference);

        if ($request->query('sort', 'forecast_score') === 'forecast_score') {
            $summaries = $summaries->sortByDesc('forecast_score')->values();
        }

        return view('forecasts.index', [
            'forecastSummaries' => $summaries,
            'sort' => $request->query('sort', 'forecast_score'),
        ]);
    }
}
