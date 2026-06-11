<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('reviews.index', [
            'reviews' => Review::query()
                ->withCount('opportunityDecisions')
                ->orderByRaw('completed_at is null')
                ->latest('completed_at')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('reviews.create', [
            'reviewTypes' => Review::reviewTypeOptions(),
            'defaultCompletedAt' => now()->format('Y-m-d\\TH:i'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'review_type' => ['required', 'string', Rule::in(Review::REVIEW_TYPES)],
            'summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'completed_at' => ['nullable', 'date'],
        ]);

        $validated['completed_at'] ??= now();

        $review = Review::create($validated);

        return redirect()
            ->route('reviews.show', $review)
            ->with('status', 'Review recorded.');
    }

    public function show(Review $review): View
    {
        return view('reviews.show', [
            'review' => $review->load([
                'opportunityDecisions' => fn ($query) => $query->with('opportunity')->latest('decided_at')->latest(),
            ]),
        ]);
    }
}
