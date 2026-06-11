<?php

use App\Support\Statuses;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalize('opportunities', [
            Statuses::OPPORTUNITY_IDEA => ['idea', 'new'],
            Statuses::OPPORTUNITY_RESEARCHING => ['research', 'researching'],
            Statuses::OPPORTUNITY_ACTIVE => ['active', 'open', 'in progress', 'working', 'pursuing'],
            Statuses::OPPORTUNITY_FOCUSED => ['focused', 'focus'],
            Statuses::OPPORTUNITY_PARKED => ['parked', 'paused'],
            Statuses::OPPORTUNITY_WON => ['won', 'accepted'],
            Statuses::OPPORTUNITY_REJECTED => ['rejected', 'declined'],
            Statuses::OPPORTUNITY_CLOSED => ['closed', 'archived'],
        ]);

        $this->normalize('applications', [
            Statuses::APPLICATION_DRAFT => ['draft'],
            Statuses::APPLICATION_APPLIED => ['applied', 'submitted'],
            Statuses::APPLICATION_INTERVIEWING => ['interviewing', 'interview'],
            Statuses::APPLICATION_OFFER => ['offer', 'offered'],
            Statuses::APPLICATION_REJECTED => ['rejected'],
            Statuses::APPLICATION_WITHDRAWN => ['withdrawn'],
            Statuses::APPLICATION_ACCEPTED => ['accepted'],
        ]);

        $this->normalize('projects', [
            Statuses::PROJECT_IDEA => ['idea'],
            Statuses::PROJECT_PLANNED => ['planned', 'planning'],
            Statuses::PROJECT_ACTIVE => ['active', 'open', 'in progress', 'working'],
            Statuses::PROJECT_COMPLETED => ['completed', 'complete', 'done'],
            Statuses::PROJECT_ARCHIVED => ['archived', 'paused', 'closed'],
        ]);

        $this->normalize('opportunity_gaps', [
            Statuses::GAP_OPEN => ['open', 'active'],
            Statuses::GAP_IN_PROGRESS => ['in progress', 'working'],
            Statuses::GAP_COMPLETE => ['complete', 'completed', 'done', 'closed'],
        ]);
    }

    public function down(): void
    {
        // Status normalization is intentionally not reversed because multiple legacy
        // spellings can map to the same controlled workflow status.
    }

    private function normalize(string $table, array $statusAliases): void
    {
        foreach ($statusAliases as $status => $aliases) {
            DB::table($table)
                ->whereIn(DB::raw('lower(trim(status))'), $aliases)
                ->update(['status' => $status]);
        }
    }
};
