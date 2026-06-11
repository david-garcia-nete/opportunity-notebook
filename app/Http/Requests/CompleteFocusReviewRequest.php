<?php

namespace App\Http\Requests;

use App\Models\OpportunityDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteFocusReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*.decision_type' => ['required', 'string', Rule::in(OpportunityDecision::FOCUS_REVIEW_DECISION_TYPES)],
            'decisions.*.reason_category' => ['required', 'string', Rule::in(OpportunityDecision::REASON_CATEGORIES)],
            'decisions.*.notes' => ['nullable', 'string'],
            'next_actions' => ['nullable', 'array'],
            'next_actions.*.title' => ['nullable', 'string', 'max:255'],
            'next_actions.*.due_date' => ['nullable', 'date'],
            'next_actions.*.description' => ['nullable', 'string'],
        ];
    }
}
