<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(): View
    {
        return view('applications.index', [
            'applications' => Application::with('opportunity')->latest('applied_at')->latest()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('applications.create', [
            'opportunities' => Opportunity::orderBy('title')->get(),
            'selectedOpportunityId' => $request->integer('opportunity_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $application = Application::create($this->validatedApplication($request));

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Application created.');
    }

    public function show(Application $application): View
    {
        return view('applications.show', [
            'application' => $application->load('opportunity'),
        ]);
    }

    public function edit(Application $application): View
    {
        return view('applications.edit', [
            'application' => $application,
            'opportunities' => Opportunity::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $application->update($this->validatedApplication($request));

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Application updated.');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $application->delete();

        return redirect()
            ->route('applications.index')
            ->with('status', 'Application deleted.');
    }

    private function validatedApplication(Request $request): array
    {
        return $request->validate([
            'opportunity_id' => ['required', 'integer', Rule::exists('opportunities', 'id')],
            'applied_at' => ['required', 'date'],
            'status' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
