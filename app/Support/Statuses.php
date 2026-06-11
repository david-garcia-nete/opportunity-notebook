<?php

namespace App\Support;

class Statuses
{
    public const OPPORTUNITY_IDEA = 'Idea';
    public const OPPORTUNITY_RESEARCHING = 'Researching';
    public const OPPORTUNITY_ACTIVE = 'Active';
    public const OPPORTUNITY_FOCUSED = 'Focused';
    public const OPPORTUNITY_PARKED = 'Parked';
    public const OPPORTUNITY_WON = 'Won';
    public const OPPORTUNITY_REJECTED = 'Rejected';
    public const OPPORTUNITY_CLOSED = 'Closed';

    public const APPLICATION_DRAFT = 'Draft';
    public const APPLICATION_APPLIED = 'Applied';
    public const APPLICATION_INTERVIEWING = 'Interviewing';
    public const APPLICATION_OFFER = 'Offer';
    public const APPLICATION_REJECTED = 'Rejected';
    public const APPLICATION_WITHDRAWN = 'Withdrawn';
    public const APPLICATION_ACCEPTED = 'Accepted';

    public const PROJECT_IDEA = 'Idea';
    public const PROJECT_PLANNED = 'Planned';
    public const PROJECT_ACTIVE = 'Active';
    public const PROJECT_COMPLETED = 'Completed';
    public const PROJECT_ARCHIVED = 'Archived';

    public const GAP_OPEN = 'Open';
    public const GAP_IN_PROGRESS = 'In Progress';
    public const GAP_COMPLETE = 'Complete';

    public static function opportunities(): array
    {
        return [
            self::OPPORTUNITY_IDEA,
            self::OPPORTUNITY_RESEARCHING,
            self::OPPORTUNITY_ACTIVE,
            self::OPPORTUNITY_FOCUSED,
            self::OPPORTUNITY_PARKED,
            self::OPPORTUNITY_WON,
            self::OPPORTUNITY_REJECTED,
            self::OPPORTUNITY_CLOSED,
        ];
    }

    public static function applications(): array
    {
        return [
            self::APPLICATION_DRAFT,
            self::APPLICATION_APPLIED,
            self::APPLICATION_INTERVIEWING,
            self::APPLICATION_OFFER,
            self::APPLICATION_REJECTED,
            self::APPLICATION_WITHDRAWN,
            self::APPLICATION_ACCEPTED,
        ];
    }

    public static function projects(): array
    {
        return [
            self::PROJECT_IDEA,
            self::PROJECT_PLANNED,
            self::PROJECT_ACTIVE,
            self::PROJECT_COMPLETED,
            self::PROJECT_ARCHIVED,
        ];
    }

    public static function gaps(): array
    {
        return [
            self::GAP_OPEN,
            self::GAP_IN_PROGRESS,
            self::GAP_COMPLETE,
        ];
    }

    public static function terminalOpportunities(): array
    {
        return [
            self::OPPORTUNITY_WON,
            self::OPPORTUNITY_REJECTED,
            self::OPPORTUNITY_CLOSED,
        ];
    }

    public static function unavailableForNextActionOpportunities(): array
    {
        return [
            self::OPPORTUNITY_PARKED,
            self::OPPORTUNITY_WON,
            self::OPPORTUNITY_REJECTED,
            self::OPPORTUNITY_CLOSED,
        ];
    }

    public static function currentOpportunities(): array
    {
        return array_values(array_diff(
            self::opportunities(),
            self::unavailableForNextActionOpportunities()
        ));
    }

    public static function normalizeOpportunity(?string $status): ?string
    {
        return self::normalize($status, [
            'idea' => self::OPPORTUNITY_IDEA,
            'new' => self::OPPORTUNITY_IDEA,
            'research' => self::OPPORTUNITY_RESEARCHING,
            'researching' => self::OPPORTUNITY_RESEARCHING,
            'active' => self::OPPORTUNITY_ACTIVE,
            'open' => self::OPPORTUNITY_ACTIVE,
            'in progress' => self::OPPORTUNITY_ACTIVE,
            'working' => self::OPPORTUNITY_ACTIVE,
            'pursuing' => self::OPPORTUNITY_ACTIVE,
            'focused' => self::OPPORTUNITY_FOCUSED,
            'focus' => self::OPPORTUNITY_FOCUSED,
            'parked' => self::OPPORTUNITY_PARKED,
            'paused' => self::OPPORTUNITY_PARKED,
            'won' => self::OPPORTUNITY_WON,
            'accepted' => self::OPPORTUNITY_WON,
            'rejected' => self::OPPORTUNITY_REJECTED,
            'declined' => self::OPPORTUNITY_REJECTED,
            'closed' => self::OPPORTUNITY_CLOSED,
            'archived' => self::OPPORTUNITY_CLOSED,
        ]);
    }

    public static function normalizeApplication(?string $status): ?string
    {
        return self::normalize($status, [
            'draft' => self::APPLICATION_DRAFT,
            'applied' => self::APPLICATION_APPLIED,
            'submitted' => self::APPLICATION_APPLIED,
            'interviewing' => self::APPLICATION_INTERVIEWING,
            'interview' => self::APPLICATION_INTERVIEWING,
            'offer' => self::APPLICATION_OFFER,
            'offered' => self::APPLICATION_OFFER,
            'rejected' => self::APPLICATION_REJECTED,
            'withdrawn' => self::APPLICATION_WITHDRAWN,
            'accepted' => self::APPLICATION_ACCEPTED,
        ]);
    }

    public static function normalizeProject(?string $status): ?string
    {
        return self::normalize($status, [
            'idea' => self::PROJECT_IDEA,
            'planned' => self::PROJECT_PLANNED,
            'planning' => self::PROJECT_PLANNED,
            'active' => self::PROJECT_ACTIVE,
            'open' => self::PROJECT_ACTIVE,
            'in progress' => self::PROJECT_ACTIVE,
            'working' => self::PROJECT_ACTIVE,
            'completed' => self::PROJECT_COMPLETED,
            'complete' => self::PROJECT_COMPLETED,
            'done' => self::PROJECT_COMPLETED,
            'archived' => self::PROJECT_ARCHIVED,
            'paused' => self::PROJECT_ARCHIVED,
            'closed' => self::PROJECT_ARCHIVED,
        ]);
    }

    public static function normalizeGap(?string $status): ?string
    {
        return self::normalize($status, [
            'open' => self::GAP_OPEN,
            'active' => self::GAP_OPEN,
            'in progress' => self::GAP_IN_PROGRESS,
            'working' => self::GAP_IN_PROGRESS,
            'complete' => self::GAP_COMPLETE,
            'completed' => self::GAP_COMPLETE,
            'done' => self::GAP_COMPLETE,
            'closed' => self::GAP_COMPLETE,
        ]);
    }

    private static function normalize(?string $status, array $aliases): ?string
    {
        if ($status === null) {
            return null;
        }

        $key = str($status)->squish()->lower()->toString();

        return $aliases[$key] ?? null;
    }
}
