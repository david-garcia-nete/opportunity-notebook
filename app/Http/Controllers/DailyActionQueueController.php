<?php

namespace App\Http\Controllers;

use App\Services\DailyActionQueueService;
use Illuminate\View\View;

class DailyActionQueueController extends Controller
{
    public function __invoke(DailyActionQueueService $dailyActionQueue): View
    {
        $queueItems = $dailyActionQueue->build();

        return view('daily-queue', [
            'queueItems' => $queueItems,
            'summary' => $dailyActionQueue->summary($queueItems),
        ]);
    }
}
