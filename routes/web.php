<?php

use App\Http\Controllers\ActionController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactOpportunityController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\OpportunityProjectController;
use App\Http\Controllers\OpportunityStrategicObjectiveController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StrategicObjectiveController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/opportunities/compare', [OpportunityController::class, 'compare'])
        ->name('opportunities.compare');
    Route::resource('opportunities', OpportunityController::class);
    Route::post('/opportunities/{opportunity}/contacts', [ContactOpportunityController::class, 'storeForOpportunity'])
        ->name('opportunities.contacts.store');
    Route::delete('/opportunities/{opportunity}/contacts/{contact}', [ContactOpportunityController::class, 'destroyFromOpportunity'])
        ->name('opportunities.contacts.destroy');
    Route::post('/opportunities/{opportunity}/projects', [OpportunityProjectController::class, 'storeForOpportunity'])
        ->name('opportunities.projects.store');
    Route::delete('/opportunities/{opportunity}/projects/{project}', [OpportunityProjectController::class, 'destroyFromOpportunity'])
        ->name('opportunities.projects.destroy');
    Route::post('/opportunities/{opportunity}/strategic-objectives', [OpportunityStrategicObjectiveController::class, 'storeForOpportunity'])
        ->name('opportunities.strategic-objectives.store');
    Route::delete('/opportunities/{opportunity}/strategic-objectives/{strategicObjective}', [OpportunityStrategicObjectiveController::class, 'destroyFromOpportunity'])
        ->name('opportunities.strategic-objectives.destroy');

    Route::resource('contacts', ContactController::class);
    Route::post('/contacts/{contact}/opportunities', [ContactOpportunityController::class, 'storeForContact'])
        ->name('contacts.opportunities.store');
    Route::delete('/contacts/{contact}/opportunities/{opportunity}', [ContactOpportunityController::class, 'destroyFromContact'])
        ->name('contacts.opportunities.destroy');
    Route::resource('actions', ActionController::class);
    Route::resource('applications', ApplicationController::class);
    Route::resource('projects', ProjectController::class);
    Route::resource('strategic-objectives', StrategicObjectiveController::class);
    Route::post('/projects/{project}/opportunities', [OpportunityProjectController::class, 'storeForProject'])
        ->name('projects.opportunities.store');
    Route::delete('/projects/{project}/opportunities/{opportunity}', [OpportunityProjectController::class, 'destroyFromProject'])
        ->name('projects.opportunities.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
