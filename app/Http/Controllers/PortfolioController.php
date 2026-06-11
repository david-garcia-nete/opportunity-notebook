<?php

namespace App\Http\Controllers;

use App\Services\PortfolioAnalysisService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __invoke(Request $request, PortfolioAnalysisService $portfolio): View
    {
        return view('portfolio.index', $portfolio->analysis($request->user()?->preference));
    }
}
