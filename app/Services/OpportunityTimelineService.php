<?php

namespace App\Services;

use App\Models\Action;
use App\Models\Application;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Support\Statuses;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OpportunityTimelineService
{
    public function __construct(private OpportunityForecastService $forecast)
    {
    }

    public function forOpportunity(Opportunity $opportunity): array
    {
        $opportunity->loadMissing([
            'actions.opportunityGap',
            'applications',
            'contactInteractions.contact',
            'opportunityGaps.actions',
        ]);

        $items = collect()
            ->merge($this->actionItems($opportunity->actions, false))
            ->merge($this->applicationItems($opportunity->applications))
            ->merge($this->contactInteractionItems($opportunity->contactInteractions))
            ->merge($this->gapItems($opportunity->opportunityGaps))
            ->merge($this->gapActionItems($opportunity))
            ->merge($this->forecastItems($opportunity));

        return $this->splitAndSort($items);
    }

    public function global(bool $focusOnly = false): array
    {
        $opportunities = Opportunity::query()
            ->whereIn('status', Statuses::currentOpportunities())
            ->when($focusOnly, fn ($query) => $query->where('is_focus', true))
            ->with([
                'actions.opportunityGap',
                'applications',
                'contactInteractions.contact',
                'opportunityGaps.actions',
            ])
            ->get();

        $items = $opportunities
            ->flatMap(function (Opportunity $opportunity) {
                $timeline = $this->forOpportunity($opportunity);

                return $timeline['upcoming']->merge($timeline['history']);
            });

        return $this->splitAndSort($items);
    }

    public function recentHistory(int $limit = 5): Collection
    {
        return $this->global()['history']->take($limit)->values();
    }

    private function actionItems(Collection $actions, bool $gapLinked): Collection
    {
        return $actions->flatMap(function (Action $action) use ($gapLinked) {
            $opportunity = $action->opportunity ?? $action->opportunityGap?->opportunity;

            $items = collect([
                $this->item(
                    date: $action->created_at,
                    typeLabel: $gapLinked ? 'Gap Action Created' : 'Action Created',
                    title: $action->title,
                    status: $action->status(),
                    opportunity: $opportunity,
                    url: route('actions.show', $action),
                    source: $action,
                ),
            ]);

            if ($action->completed_at !== null) {
                $items->push($this->item(
                    date: $action->completed_at,
                    typeLabel: $gapLinked ? 'Gap Action Completed' : 'Action Completed',
                    title: $action->title,
                    status: $action->status(),
                    opportunity: $opportunity,
                    url: route('actions.show', $action),
                    source: $action,
                ));
            } elseif ($action->due_date !== null) {
                $items->push($this->item(
                    date: $action->due_date,
                    typeLabel: $gapLinked ? 'Gap Action Due' : 'Action Due',
                    title: $action->title,
                    status: $action->status(),
                    opportunity: $opportunity,
                    url: route('actions.show', $action),
                    source: $action,
                    upcoming: true,
                ));
            }

            return $items;
        });
    }

    private function applicationItems(Collection $applications): Collection
    {
        return $applications->map(fn (Application $application) => $this->item(
            date: $application->applied_at,
            typeLabel: 'Application Submitted',
            title: $application->source ? 'Application via '.$application->source : 'Application submitted',
            status: $application->status,
            opportunity: $application->opportunity,
            url: route('applications.show', $application),
            source: $application,
        ));
    }

    private function contactInteractionItems(Collection $interactions): Collection
    {
        return $interactions->flatMap(function (ContactInteraction $interaction) {
            $items = collect([
                $this->item(
                    date: $interaction->interaction_date,
                    typeLabel: 'Contact Interaction',
                    title: $interaction->summary,
                    status: $interaction->interaction_type,
                    contact: $interaction->contact?->name,
                    opportunity: $interaction->opportunity,
                    url: route('contact-interactions.edit', $interaction),
                    source: $interaction,
                ),
            ]);

            if ($interaction->next_follow_up_date !== null) {
                $items->push($this->item(
                    date: $interaction->next_follow_up_date,
                    typeLabel: 'Follow-Up Due',
                    title: $interaction->summary,
                    status: $interaction->interaction_type,
                    contact: $interaction->contact?->name,
                    opportunity: $interaction->opportunity,
                    url: route('contact-interactions.edit', $interaction),
                    source: $interaction,
                    upcoming: true,
                ));
            }

            return $items;
        });
    }

    private function gapItems(Collection $gaps): Collection
    {
        return $gaps->flatMap(function (OpportunityGap $gap) {
            $items = collect([
                $this->item(
                    date: $gap->created_at,
                    typeLabel: 'Gap Opened',
                    title: $gap->title,
                    status: $gap->status,
                    opportunity: $gap->opportunity,
                    url: route('opportunities.gaps.show', [$gap->opportunity, $gap]),
                    source: $gap,
                ),
            ]);

            if ($gap->status === Statuses::GAP_COMPLETE) {
                $items->push($this->item(
                    date: $gap->updated_at,
                    typeLabel: 'Gap Completed',
                    title: $gap->title,
                    status: $gap->status,
                    opportunity: $gap->opportunity,
                    url: route('opportunities.gaps.show', [$gap->opportunity, $gap]),
                    source: $gap,
                ));
            }

            return $items;
        });
    }

    private function gapActionItems(Opportunity $opportunity): Collection
    {
        $gapActions = $opportunity->opportunityGaps
            ->flatMap->actions
            ->filter(fn (Action $action) => $action->opportunity_id === null)
            ->each(fn (Action $action) => $action->setRelation('opportunityGap', $action->opportunityGap ?? $opportunity->opportunityGaps->firstWhere('id', $action->opportunity_gap_id)));

        return $this->actionItems($gapActions, true);
    }


    private function forecastItems(Opportunity $opportunity): Collection
    {
        $score = $this->forecast->score($opportunity, auth()->user()?->preference);

        if ($score < 60) {
            return collect([
                $this->item(
                    date: now(),
                    typeLabel: 'Forecast Alert',
                    title: 'Forecast dropped below 60',
                    status: $this->forecast->statusForScore($score),
                    opportunity: $opportunity,
                    url: route('opportunities.show', $opportunity),
                    source: $opportunity,
                ),
            ]);
        }

        if ($score >= 75) {
            return collect([
                $this->item(
                    date: now(),
                    typeLabel: 'Forecast Update',
                    title: 'Forecast improved above 75',
                    status: $this->forecast->statusForScore($score),
                    opportunity: $opportunity,
                    url: route('opportunities.show', $opportunity),
                    source: $opportunity,
                ),
            ]);
        }

        return collect();
    }

    private function item(
        Carbon|string|null $date,
        string $typeLabel,
        string $title,
        ?string $status,
        ?Opportunity $opportunity,
        ?string $url,
        object $source,
        ?string $contact = null,
        bool $upcoming = false,
    ): array {
        return [
            'date' => $date instanceof Carbon ? $date : ($date ? Carbon::parse($date) : null),
            'type_label' => $typeLabel,
            'title' => $title,
            'status' => $status,
            'contact' => $contact,
            'opportunity' => $opportunity,
            'url' => $url,
            'source' => $source,
            'upcoming' => $upcoming,
        ];
    }

    private function splitAndSort(Collection $items): array
    {
        $datedItems = $items->filter(fn (array $item) => $item['date'] !== null);

        return [
            'upcoming' => $datedItems
                ->where('upcoming', true)
                ->sortBy(fn (array $item) => [$item['date']->timestamp, $item['type_label'], $item['title']])
                ->values(),
            'history' => $datedItems
                ->where('upcoming', false)
                ->sortByDesc(fn (array $item) => $item['date']->timestamp)
                ->values(),
        ];
    }
}
