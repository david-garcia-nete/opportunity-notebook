<?php

namespace App\Http\Requests;

use App\Models\OpportunityDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_id' => ['nullable', 'integer', Rule::exists('reviews', 'id')],
            'decision_type' => ['required', 'string', Rule::in(OpportunityDecision::DECISION_TYPES)],
            'reason_category' => ['required', 'string', Rule::in(OpportunityDecision::REASON_CATEGORIES)],
            'notes' => ['nullable', 'string'],
            'decided_at' => ['required', 'date'],
        ];
    }
}
