<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Theme;
use App\Support\Statuses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('projects.index', [
            'projects' => Project::latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('projects.create', [
            'availableThemes' => $this->availableThemes(),
            'defaultStatus' => Statuses::PROJECT_ACTIVE,
            'selectedThemes' => collect(),
            'statuses' => Statuses::projects(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = Project::create($this->validatedProject($request));
        $project->themes()->sync($request->input('theme_ids', []));

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        return view('projects.show', [
            'availableOpportunities' => Opportunity::orderBy('title')->get(),
            'project' => $project->load([
                'opportunities' => fn ($query) => $query->orderBy('title'),
                'themes' => fn ($query) => $query->orderByRaw('priority is null')->orderBy('priority')->orderBy('name'),
            ]),
        ]);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'availableThemes' => $this->availableThemes($project),
            'project' => $project,
            'selectedThemes' => $project->themes,
            'statuses' => Statuses::projects(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validatedProject($request));
        $project->themes()->sync($request->input('theme_ids', []));

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('status', 'Project deleted.');
    }

    private function validatedProject(Request $request): array
    {
        if ($normalizedStatus = Statuses::normalizeProject($request->input('status'))) {
            $request->merge(['status' => $normalizedStatus]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(Statuses::projects())],
            'theme_ids' => ['nullable', 'array'],
            'theme_ids.*' => ['integer', Rule::exists('themes', 'id')],
        ]);

        unset($validated['theme_ids']);

        return $validated;
    }

    private function availableThemes(?Project $project = null)
    {
        return Theme::query()
            ->where('active', true)
            ->when($project, fn ($query) => $query->orWhereIn('id', $project->themes()->pluck('themes.id')))
            ->orderByDesc('active')
            ->orderByRaw('priority is null')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
    }
}
