<?php

use App\Http\Controllers\ActionController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactOpportunityController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\OpportunityProjectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Models\Action;
use App\Models\Application;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Project;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $opportunityCount = Opportunity::count();
    $activeOpportunityCount = Opportunity::whereNotIn('status', ['rejected', 'closed'])->count();
    $actionCount = Action::count();
    $contactCount = Contact::count();
    $applicationCount = Application::count();
    $projectCount = Project::count();
    $applicationsThisWeekCount = Application::where('applied_at', '>=', now()->subDays(7))->count();
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
        'contactCount' => $contactCount,
        'applicationCount' => $applicationCount,
        'projectCount' => $projectCount,
        'applicationsThisWeekCount' => $applicationsThisWeekCount,
        'actionsDueTodayCount' => $actionsDueTodayCount,
        'overdueActionCount' => $overdueActionCount,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('opportunities', OpportunityController::class);
    Route::post('/opportunities/{opportunity}/contacts', [ContactOpportunityController::class, 'storeForOpportunity'])
        ->name('opportunities.contacts.store');
    Route::delete('/opportunities/{opportunity}/contacts/{contact}', [ContactOpportunityController::class, 'destroyFromOpportunity'])
        ->name('opportunities.contacts.destroy');
    Route::post('/opportunities/{opportunity}/projects', [OpportunityProjectController::class, 'storeForOpportunity'])
        ->name('opportunities.projects.store');
    Route::delete('/opportunities/{opportunity}/projects/{project}', [OpportunityProjectController::class, 'destroyFromOpportunity'])
        ->name('opportunities.projects.destroy');

    Route::resource('contacts', ContactController::class);
    Route::post('/contacts/{contact}/opportunities', [ContactOpportunityController::class, 'storeForContact'])
        ->name('contacts.opportunities.store');
    Route::delete('/contacts/{contact}/opportunities/{opportunity}', [ContactOpportunityController::class, 'destroyFromContact'])
        ->name('contacts.opportunities.destroy');
    Route::resource('actions', ActionController::class);
    Route::resource('applications', ApplicationController::class);
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/opportunities', [OpportunityProjectController::class, 'storeForProject'])
        ->name('projects.opportunities.store');
    Route::delete('/projects/{project}/opportunities/{opportunity}', [OpportunityProjectController::class, 'destroyFromProject'])
        ->name('projects.opportunities.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
