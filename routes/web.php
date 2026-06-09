<?php

use App\Http\Controllers\ActionController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ProfileController;
use App\Models\Action;
use App\Models\Opportunity;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $opportunityCount = Opportunity::count();
    $activeOpportunityCount = Opportunity::whereNotIn('status', ['rejected', 'closed'])->count();
    $actionCount = Action::count();
    $actionsDueTodayCount = Action::whereDate('due_date', today())
        ->whereNull('completed_at')
        ->count();
    $overdueActionCount = Action::whereDate('due_date', '<', today())
        ->whereNull('completed_at')
        ->count();

    return view('dashboard', [
        'opportunityCount' => $opportunityCount,
        'activeOpportunityCount' => $activeOpportunityCount,
        'actionCount' => $actionCount,
        'actionsDueTodayCount' => $actionsDueTodayCount,
        'overdueActionCount' => $overdueActionCount,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('opportunities', OpportunityController::class);
    Route::resource('actions', ActionController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
