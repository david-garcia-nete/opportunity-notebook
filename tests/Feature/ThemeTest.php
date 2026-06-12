<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Project;
use App\Models\StrategicObjective;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_theme(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('themes.store'), [
            'name' => 'AI Systems',
            'description' => 'Strategic work around AI-enabled systems.',
            'priority' => 1,
            'active' => '1',
        ]);

        $theme = Theme::first();

        $response->assertRedirect(route('themes.show', $theme));
        $this->assertDatabaseHas('themes', [
            'name' => 'AI Systems',
            'priority' => 1,
            'active' => true,
        ]);
    }

    public function test_user_can_edit_a_theme(): void
    {
        $user = User::factory()->create();
        $theme = Theme::create([
            'name' => 'Consulting',
            'description' => 'Original description.',
            'priority' => 3,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('themes.update', $theme), [
            'name' => 'Consulting Growth',
            'description' => 'Sharper consulting arena.',
            'priority' => 2,
        ]);

        $response->assertRedirect(route('themes.show', $theme));
        $this->assertDatabaseHas('themes', [
            'id' => $theme->id,
            'name' => 'Consulting Growth',
            'description' => 'Sharper consulting arena.',
            'priority' => 2,
            'active' => false,
        ]);
    }

    public function test_user_can_attach_themes_to_an_opportunity(): void
    {
        $user = User::factory()->create();
        $theme = Theme::create(['name' => 'Product Development', 'active' => true]);

        $response = $this->actingAs($user)->post(route('opportunities.store'), [
            'title' => 'Launch paid notebook feature',
            'status' => 'Idea',
            'theme_ids' => [$theme->id],
        ]);

        $opportunity = Opportunity::first();

        $response->assertRedirect(route('opportunities.show', $opportunity));
        $this->assertTrue($opportunity->themes()->whereKey($theme->id)->exists());
    }

    public function test_opportunity_show_page_displays_attached_themes(): void
    {
        $user = User::factory()->create();
        $theme = Theme::create(['name' => 'Writing', 'active' => true]);
        $opportunity = Opportunity::create(['title' => 'Write premium guide', 'status' => 'Active']);
        $opportunity->themes()->attach($theme);

        $response = $this->actingAs($user)->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSeeText('Themes')
            ->assertSeeText('Writing');
    }

    public function test_theme_show_page_displays_linked_opportunities(): void
    {
        $user = User::factory()->create();
        $theme = Theme::create(['name' => 'Local Networking', 'active' => true]);
        $opportunity = Opportunity::create(['title' => 'Chamber advisory lead', 'status' => 'Active']);
        $project = Project::create(['name' => 'Relationship Map', 'status' => 'Active']);
        $objective = StrategicObjective::create(['name' => 'Build referral pipeline', 'priority' => 7, 'active' => true]);
        $theme->opportunities()->attach($opportunity);
        $theme->projects()->attach($project);
        $theme->strategicObjectives()->attach($objective);

        $response = $this->actingAs($user)->get(route('themes.show', $theme));

        $response
            ->assertOk()
            ->assertSeeText('Chamber advisory lead')
            ->assertSeeText('Relationship Map')
            ->assertSeeText('Build referral pipeline')
            ->assertSeeText('Active');
    }

    public function test_inactive_themes_do_not_appear_as_preferred_selectable_options_unless_already_attached(): void
    {
        $user = User::factory()->create();
        $activeTheme = Theme::create(['name' => 'Employment', 'active' => true]);
        $inactiveTheme = Theme::create(['name' => 'Archived Music', 'active' => false]);
        $opportunity = Opportunity::create(['title' => 'Session work', 'status' => 'Active']);
        $opportunity->themes()->attach($inactiveTheme);

        $createResponse = $this->actingAs($user)->get(route('opportunities.create'));
        $createResponse
            ->assertOk()
            ->assertSeeText('Employment')
            ->assertDontSeeText('Archived Music');

        $editResponse = $this->actingAs($user)->get(route('opportunities.edit', $opportunity));
        $editResponse
            ->assertOk()
            ->assertSeeText('Archived Music');

        $this->assertTrue($activeTheme->exists);
    }

    public function test_theme_portfolio_counts_are_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $theme = Theme::create(['name' => 'Consulting', 'active' => true]);
        $won = $this->scoredOpportunity('Won consulting deal', ['is_focus' => true, 'outcome' => 'Won', 'outcome_date' => today()]);
        $lost = $this->scoredOpportunity('Lost consulting deal', ['outcome' => 'Lost', 'outcome_date' => today()]);
        $abandoned = $this->scoredOpportunity('Abandoned consulting lead', ['outcome' => 'Abandoned', 'outcome_date' => today()]);
        $theme->opportunities()->attach([$won->id, $lost->id, $abandoned->id]);

        $response = $this->actingAs($user)->get(route('portfolio'));
        $section = $this->section($response->getContent(), 'theme-portfolio');

        $this->assertStringContainsString('Consulting', $section);
        $this->assertStringContainsString('>3<', $section);
        $this->assertStringContainsString('>1<', $section);
        $this->assertStringContainsString('>58.0<', $section);
    }

    private function scoredOpportunity(string $title, array $attributes = []): Opportunity
    {
        return Opportunity::create(array_merge([
            'title' => $title,
            'status' => 'Active',
            'income_potential' => 10,
            'probability_of_success' => 10,
            'time_to_revenue' => 1,
            'strategic_alignment' => 10,
            'personal_interest' => 10,
            'skill_growth' => 10,
            'family_fit' => 10,
            'risk_level' => 1,
        ], $attributes));
    }

    private function section(string $html, string $testId): string
    {
        $start = strpos($html, 'data-testid="'.$testId.'"');

        if ($start === false) {
            return '';
        }

        $end = strpos($html, '</section>', $start);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }
}
