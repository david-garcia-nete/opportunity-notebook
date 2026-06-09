<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActionController extends Controller
{
    public function index(): View
    {
        return view('actions.index', [
            'actions' => Action::with('opportunity')->latest('due_date')->latest()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('actions.create', [
            'opportunities' => Opportunity::orderBy('title')->get(),
            'selectedOpportunityId' => $request->integer('opportunity_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $action = Action::create($this->validatedAction($request));

        return redirect()
            ->route('actions.show', $action)
            ->with('status', 'Action created.');
    }

    public function show(Action $action): View
    {
        return view('actions.show', [
            'action' => $action->load('opportunity'),
        ]);
    }

    public function edit(Action $action): View
    {
        return view('actions.edit', [
            'action' => $action,
            'opportunities' => Opportunity::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, Action $action): RedirectResponse
    {
        $action->update($this->validatedAction($request));

        return redirect()
            ->route('actions.show', $action)
            ->with('status', 'Action updated.');
    }

    public function destroy(Action $action): RedirectResponse
    {
        $action->delete();

        return redirect()
            ->route('actions.index')
            ->with('status', 'Action deleted.');
    }

    private function validatedAction(Request $request): array
    {
        return $request->validate([
            'opportunity_id' => ['nullable', 'integer', Rule::exists('opportunities', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ]);
    }
}
