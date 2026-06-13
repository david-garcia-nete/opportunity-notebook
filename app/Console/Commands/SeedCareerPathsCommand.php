<?php

namespace App\Console\Commands;

use App\Models\Action;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\Theme;
use App\Models\User;
use App\Models\UserPreference;
use App\Support\Statuses;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class SeedCareerPathsCommand extends Command
{
    protected $signature = 'opportunity:seed-career-paths';

    protected $description = 'Seed David Garcia\'s initial career-path opportunities, themes, gaps, actions, and scoring weights.';

    /**
     * @var array<string, array{created: int, updated: int, unchanged: int}>
     */
    private array $summary = [
        'themes' => ['created' => 0, 'updated' => 0, 'unchanged' => 0],
        'opportunities' => ['created' => 0, 'updated' => 0, 'unchanged' => 0],
        'gaps' => ['created' => 0, 'updated' => 0, 'unchanged' => 0],
        'actions' => ['created' => 0, 'updated' => 0, 'unchanged' => 0],
        'preferences' => ['created' => 0, 'updated' => 0, 'unchanged' => 0],
    ];

    public function handle(): int
    {
        $themes = collect($this->themes())
            ->mapWithKeys(fn (string $themeName, int $index): array => [
                $themeName => $this->upsertTheme($themeName, $index + 1),
            ]);

        foreach ($this->opportunities() as $opportunityData) {
            $opportunity = $this->upsertOpportunity($opportunityData);
            $opportunity->themes()->syncWithoutDetaching([$themes[$opportunityData['theme']]->id]);

            foreach ($opportunityData['gaps'] as $gapTitle) {
                $this->upsertGap($opportunity, $gapTitle);
            }

            foreach ($opportunityData['actions'] as $actionTitle) {
                $this->upsertAction($opportunity, $actionTitle);
            }
        }

        $this->upsertPreferences();
        $this->printSummary();

        return self::SUCCESS;
    }

    private function upsertTheme(string $name, int $priority): Theme
    {
        $theme = Theme::firstOrNew(['name' => $name]);
        $this->saveAndCount($theme, [
            'description' => $this->themeDescription($name),
            'priority' => $priority,
            'active' => true,
        ], 'themes');

        return $theme;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function upsertOpportunity(array $data): Opportunity
    {
        $opportunity = Opportunity::firstOrNew(['title' => $data['title']]);
        $attributes = [
            'type' => 'Career Path',
            'status' => Statuses::OPPORTUNITY_ACTIVE,
            'is_focus' => $data['focus'],
            'focused_at' => $data['focus'] ? ($opportunity->focused_at ?? Carbon::now()) : null,
            'focus_reason' => $data['focus'] ? 'Initial personal career-path seed.' : null,
            ...$data['scores'],
        ];

        $this->saveAndCount($opportunity, $attributes, 'opportunities');

        return $opportunity;
    }

    private function upsertGap(Opportunity $opportunity, string $title): OpportunityGap
    {
        $gap = OpportunityGap::firstOrNew([
            'opportunity_id' => $opportunity->id,
            'title' => $title,
        ]);

        $attributes = [
            'description' => null,
            'category' => $this->gapCategory($title),
            'priority' => $this->gapPriority($title),
        ];

        if (! $gap->exists || blank($gap->status)) {
            $attributes['status'] = Statuses::GAP_OPEN;
        }

        $this->saveAndCount($gap, $attributes, 'gaps');

        return $gap;
    }

    private function upsertAction(Opportunity $opportunity, string $title): Action
    {
        $action = Action::firstOrNew([
            'opportunity_id' => $opportunity->id,
            'title' => $title,
        ]);

        $attributes = [
            'description' => null,
        ];

        if (! $action->exists) {
            $attributes['due_date'] = null;
            $attributes['completed_at'] = null;
        }

        $this->saveAndCount($action, $attributes, 'actions');

        return $action;
    }

    private function upsertPreferences(): void
    {
        $user = User::query()->oldest('id')->first();

        if (! $user) {
            $user = User::create([
                'name' => 'David Garcia',
                'email' => 'david@example.com',
                'password' => Hash::make(str()->password()),
            ]);
        }

        $preference = UserPreference::firstOrNew(['user_id' => $user->id]);
        $this->saveAndCount($preference, [
            'income_weight' => 9,
            'probability_weight' => 9,
            'time_to_revenue_weight' => 9,
            'strategic_alignment_weight' => 8,
            'personal_interest_weight' => 5,
            'skill_growth_weight' => 6,
            'family_fit_weight' => 10,
            'risk_weight' => 8,
        ], 'preferences');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function saveAndCount(Theme|Opportunity|OpportunityGap|Action|UserPreference $model, array $attributes, string $bucket): void
    {
        $exists = $model->exists;
        $model->fill($attributes);

        if (! $exists) {
            $model->save();
            $this->summary[$bucket]['created']++;

            return;
        }

        if ($model->isDirty()) {
            $model->save();
            $this->summary[$bucket]['updated']++;

            return;
        }

        $this->summary[$bucket]['unchanged']++;
    }

    private function printSummary(): void
    {
        $this->info('Career path seed complete.');
        $this->newLine();

        foreach ($this->summary as $label => $counts) {
            $this->line(sprintf(
                '%s: %d created, %d updated, %d unchanged',
                str($label)->title(),
                $counts['created'],
                $counts['updated'],
                $counts['unchanged'],
            ));
        }
    }

    /**
     * @return list<string>
     */
    private function themes(): array
    {
        return [
            'Employment',
            'Consulting',
            'Skilled Trades',
            'Music',
        ];
    }

    private function themeDescription(string $name): string
    {
        return match ($name) {
            'Employment' => 'Career-path opportunities pursued through full-time or staff employment.',
            'Consulting' => 'Independent consulting or freelance development income paths.',
            'Skilled Trades' => 'Hands-on trade career paths and apprenticeship options.',
            'Music' => 'Music income experiments and fulfillment-oriented creative work.',
            default => 'Personal career decision theme.',
        };
    }

    private function gapCategory(string $title): string
    {
        $lowerTitle = str($title)->lower();

        return match (true) {
            $lowerTitle->contains(['resume', 'positioning', 'language']) => 'Experience',
            $lowerTitle->contains(['employer', 'companies', 'roles', 'prospects', 'lead source', 'postings']) => 'Networking',
            $lowerTitle->contains(['project', 'portfolio', 'examples']) => 'Portfolio',
            $lowerTitle->contains(['practice', 'skill']) => 'Skill',
            default => 'Other',
        };
    }

    private function gapPriority(string $title): string
    {
        return str($title)->lower()->contains(['defined offer', 'lead source', 'target companies'])
            ? 'Critical'
            : 'High';
    }

    /**
     * @return list<array{title: string, theme: string, focus: bool, scores: array<string, int>, gaps: list<string>, actions: list<string>}>
     */
    private function opportunities(): array
    {
        return [
            [
                'title' => 'Backend Laravel / PHP Developer',
                'theme' => 'Employment',
                'focus' => true,
                'scores' => ['income_potential' => 8, 'probability_of_success' => 8, 'time_to_revenue' => 8, 'strategic_alignment' => 9, 'personal_interest' => 7, 'skill_growth' => 6, 'family_fit' => 9, 'risk_level' => 3],
                'gaps' => ['Need targeted employer list', 'Need updated resume', 'Need interview practice'],
                'actions' => ['Apply to 5 targeted backend jobs', 'Update resume', 'Reach out to former coworkers'],
            ],
            [
                'title' => 'AI-Enabled Developer',
                'theme' => 'Employment',
                'focus' => true,
                'scores' => ['income_potential' => 9, 'probability_of_success' => 6, 'time_to_revenue' => 6, 'strategic_alignment' => 9, 'personal_interest' => 8, 'skill_growth' => 9, 'family_fit' => 8, 'risk_level' => 5],
                'gaps' => ['Need AI project examples', 'Need stronger positioning', 'Need target companies'],
                'actions' => ['Create AI-enabled developer resume version', 'Identify 20 AI-adjacent developer roles'],
            ],
            [
                'title' => 'Inventory / Data Analyst',
                'theme' => 'Employment',
                'focus' => true,
                'scores' => ['income_potential' => 6, 'probability_of_success' => 8, 'time_to_revenue' => 8, 'strategic_alignment' => 6, 'personal_interest' => 5, 'skill_growth' => 6, 'family_fit' => 9, 'risk_level' => 2],
                'gaps' => ['Translate experience into analyst language', 'SQL portfolio examples'],
                'actions' => ['Gather 10 analyst job postings', 'Build one inventory analysis project'],
            ],
            [
                'title' => 'Tech Operations / Systems Lead',
                'theme' => 'Employment',
                'focus' => true,
                'scores' => ['income_potential' => 8, 'probability_of_success' => 7, 'time_to_revenue' => 7, 'strategic_alignment' => 8, 'personal_interest' => 6, 'skill_growth' => 7, 'family_fit' => 8, 'risk_level' => 4],
                'gaps' => ['Resume positioning', 'Leadership examples'],
                'actions' => ['Create operations-focused resume'],
            ],
            [
                'title' => 'Consulting / Freelance Development',
                'theme' => 'Consulting',
                'focus' => false,
                'scores' => ['income_potential' => 8, 'probability_of_success' => 4, 'time_to_revenue' => 4, 'strategic_alignment' => 7, 'personal_interest' => 8, 'skill_growth' => 7, 'family_fit' => 5, 'risk_level' => 7],
                'gaps' => ['Defined offer', 'Lead source', 'Pricing'],
                'actions' => ['Define one service', 'Identify 10 prospects'],
            ],
            [
                'title' => 'Business Analytics Path',
                'theme' => 'Employment',
                'focus' => false,
                'scores' => ['income_potential' => 7, 'probability_of_success' => 6, 'time_to_revenue' => 5, 'strategic_alignment' => 7, 'personal_interest' => 6, 'skill_growth' => 8, 'family_fit' => 8, 'risk_level' => 4],
                'gaps' => ['Portfolio projects', 'Analytics resume'],
                'actions' => ['Create analytics resume draft', 'Identify 10 business analytics roles'],
            ],
            [
                'title' => 'Electrician Apprenticeship',
                'theme' => 'Skilled Trades',
                'focus' => false,
                'scores' => ['income_potential' => 7, 'probability_of_success' => 7, 'time_to_revenue' => 5, 'strategic_alignment' => 4, 'personal_interest' => 5, 'skill_growth' => 8, 'family_fit' => 6, 'risk_level' => 4],
                'gaps' => ['Understand pay progression', 'Understand schedule impact', 'Talk to actual apprentices'],
                'actions' => ['Attend IBEW information session', 'Interview one apprentice'],
            ],
            [
                'title' => 'Music Income',
                'theme' => 'Music',
                'focus' => false,
                'scores' => ['income_potential' => 3, 'probability_of_success' => 2, 'time_to_revenue' => 2, 'strategic_alignment' => 4, 'personal_interest' => 10, 'skill_growth' => 7, 'family_fit' => 7, 'risk_level' => 8],
                'gaps' => ['Realistic revenue model', 'Audience or customer path', 'Weekly time boundary'],
                'actions' => ['Define whether this is income path or personal fulfillment path'],
            ],
        ];
    }
}
