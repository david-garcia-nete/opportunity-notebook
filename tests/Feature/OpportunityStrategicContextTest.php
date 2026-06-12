<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityDecision;
use App\Models\OpportunityGap;
use App\Models\Review;
use App\Models\StrategicObjective;
use App\Models\Theme;
use App\Models\User;
use App\Services\OpportunityStrategicContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityStrategicContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_builds_context_for_an_opportunity(): void
    {
        $opportunity = $this->opportunity([
            'title' => 'Fractional CTO Retainer',
            'company' => 'Acme Labs',
            'type' => 'Consulting',
            'status' => 'Active',
            'is_focus' => true,
            'focused_at' => now(),
            'focus_reason' => 'High strategic fit.',
        ]);
        $objective = StrategicObjective::create(['name' => 'Grow advisory revenue', 'priority' => 9, 'active' => true]);
        $opportunity->strategicObjectives()->attach($objective);

        $context = app(OpportunityStrategicContextService::class)->build($opportunity);

        $this->assertSame('Fractional CTO Retainer', $context['identity']['title']);
        $this->assertSame('Acme Labs', $context['identity']['company']);
        $this->assertSame('Consulting', $context['identity']['type']);
        $this->assertSame('Active', $context['identity']['status']);
        $this->assertSame('Focus', $context['focus']['state']);
        $this->assertSame('High strategic fit.', $context['focus']['reason']);
        $this->assertArrayHasKey('forecast_score', $context['scores']);
        $this->assertArrayHasKey('readiness_status', $context['scores']);
        $this->assertSame('Grow advisory revenue', $context['strategic_objectives'][0]['name']);
    }

    public function test_context_includes_decisions(): void
    {
        $opportunity = $this->opportunity();
        $review = Review::create(['review_type' => 'focus', 'started_at' => now(), 'completed_at' => now()]);
        OpportunityDecision::create([
            'opportunity_id' => $opportunity->id,
            'review_id' => $review->id,
            'decision_type' => 'intensify',
            'reason_category' => 'financial_return',
            'notes' => 'Pipeline value justifies more time.',
            'decided_at' => now(),
        ]);

        $context = app(OpportunityStrategicContextService::class)->build($opportunity);

        $this->assertSame('intensify', $context['decisions']['recent'][0]['decision_type']);
        $this->assertSame('Intensify', $context['decisions']['recent'][0]['decision_type_label']);
        $this->assertSame('financial_return', $context['decisions']['recent'][0]['reason_category']);
        $this->assertSame('focus', $context['decisions']['latest_review_linked'][0]['review_type']);
    }

    public function test_context_includes_outcome_learning_fields(): void
    {
        $opportunity = $this->opportunity([
            'outcome' => 'Lost',
            'outcome_date' => today(),
            'outcome_reason' => 'competition',
            'outcome_notes' => 'Client selected incumbent vendor.',
            'lesson_learned' => 'Warm champion access matters earlier.',
        ]);

        $context = app(OpportunityStrategicContextService::class)->build($opportunity);

        $this->assertSame('Lost', $context['outcome_learning']['outcome']);
        $this->assertSame('competition', $context['outcome_learning']['outcome_reason']);
        $this->assertSame('Competition', $context['outcome_learning']['outcome_reason_label']);
        $this->assertSame('Client selected incumbent vendor.', $context['outcome_learning']['outcome_notes']);
        $this->assertSame('Warm champion access matters earlier.', $context['outcome_learning']['lesson_learned']);
    }

    public function test_context_includes_themes(): void
    {
        $opportunity = $this->opportunity();
        $theme = Theme::create(['name' => 'Advisory', 'description' => 'Higher leverage client work.', 'priority' => 1, 'active' => true]);
        $opportunity->themes()->attach($theme);

        $context = app(OpportunityStrategicContextService::class)->build($opportunity);

        $this->assertSame('Advisory', $context['themes'][0]['name']);
        $this->assertSame('Higher leverage client work.', $context['themes'][0]['description']);
        $this->assertTrue($context['themes'][0]['active']);
    }

    public function test_context_includes_actions_and_gaps(): void
    {
        $opportunity = $this->opportunity();
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Send proposal',
            'due_date' => today()->addDay(),
        ]);
        Action::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Completed discovery call',
            'completed_at' => now(),
        ]);
        OpportunityGap::create([
            'opportunity_id' => $opportunity->id,
            'title' => 'Missing enterprise case study',
            'category' => 'Portfolio',
            'status' => 'Open',
            'priority' => 'Critical',
        ]);

        $context = app(OpportunityStrategicContextService::class)->build($opportunity);

        $this->assertSame('Send proposal', $context['actions']['open'][0]['title']);
        $this->assertSame('Completed discovery call', $context['actions']['completed_recent'][0]['title']);
        $this->assertSame('Missing enterprise case study', $context['gaps']['open'][0]['title']);
        $this->assertSame('Missing enterprise case study', $context['gaps']['critical'][0]['title']);
    }

    public function test_context_includes_contacts_and_recent_contact_interactions(): void
    {
        $opportunity = $this->opportunity();
        $contact = Contact::create(['name' => 'Maya Chen', 'organization' => 'Acme Labs']);
        $opportunity->contacts()->attach($contact, ['relationship_type' => 'Sponsor', 'notes' => 'Budget owner']);
        ContactInteraction::create([
            'contact_id' => $contact->id,
            'opportunity_id' => $opportunity->id,
            'interaction_date' => today(),
            'interaction_type' => 'Meeting',
            'summary' => 'Discussed scope and timing.',
            'outcome' => 'Requested proposal.',
        ]);

        $context = app(OpportunityStrategicContextService::class)->build($opportunity);

        $this->assertSame('Maya Chen', $context['contacts'][0]['name']);
        $this->assertSame('Sponsor', $context['contacts'][0]['relationship_type']);
        $this->assertSame('Discussed scope and timing.', $context['recent_contact_interactions'][0]['summary']);
    }

    public function test_opportunity_page_links_to_strategic_context_page(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity(['title' => 'Strategic Context Link']);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Strategic Context')
            ->assertSee(route('opportunities.strategic-context', $opportunity));
    }

    public function test_strategic_context_page_displays_structured_context(): void
    {
        $user = User::factory()->create();
        $opportunity = $this->opportunity([
            'title' => 'Structured Context Page',
            'outcome' => 'Won',
            'outcome_date' => today(),
            'outcome_reason' => 'strong_relationship',
            'lesson_learned' => 'Relationships shortened the sale.',
        ]);
        $theme = Theme::create(['name' => 'Retainers', 'active' => true]);
        $opportunity->themes()->attach($theme);

        $response = $this->actingAs($user)->get(route('opportunities.strategic-context', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Opportunity Strategic Context')
            ->assertSeeText('Structured Context Page')
            ->assertSeeText('Scores')
            ->assertSeeText('Themes')
            ->assertSeeText('Outcome Learning')
            ->assertSeeText('Relationships shortened the sale.')
            ->assertSeeText('Retainers');
    }

    private function opportunity(array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => 'Strategic Opportunity',
            'status' => 'Active',
            'income_potential' => 8,
            'probability_of_success' => 7,
            'time_to_revenue' => 3,
            'strategic_alignment' => 9,
            'personal_interest' => 8,
            'skill_growth' => 7,
            'family_fit' => 8,
            'risk_level' => 4,
        ], $attributes));
    }
}
