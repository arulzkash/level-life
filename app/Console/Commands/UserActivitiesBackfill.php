<?php

namespace App\Console\Commands;

use App\Services\UserDailyActivityService;
use App\Support\CacheBuster;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class UserActivitiesBackfill extends Command
{
    protected $signature = 'user-activities:backfill {--user= : Backfill only one user ID}';

    protected $description = 'Backfill daily quest completion aggregates from quest_completions';

    public function handle(UserDailyActivityService $activityService): int
    {
        $userId = $this->option('user');
        $batchedRows = collect();
        $processedRows = 0;
        $invalidatedUserIds = [];

        foreach ($activityService->groupedCompletionCountsQuery($userId)->cursor() as $row) {
            $batchedRows->push($row);

            if ($batchedRows->count() >= 500) {
                $this->flushBatch($activityService, $batchedRows, $invalidatedUserIds);
                $processedRows += 500;
                $batchedRows = collect();
            }
        }

        if ($batchedRows->isNotEmpty()) {
            $processedRows += $batchedRows->count();
            $this->flushBatch($activityService, $batchedRows, $invalidatedUserIds);
        }

        foreach (array_keys($invalidatedUserIds) as $invalidatedUserId) {
            CacheBuster::invalidatePublicProfileStats((int) $invalidatedUserId);
            CacheBuster::invalidatePublicProfileHeatmap((int) $invalidatedUserId);
        }

        $this->info("Backfill done. Daily rows synced: {$processedRows}");

        return self::SUCCESS;
    }

    private function flushBatch(
        UserDailyActivityService $activityService,
        Collection $rows,
        array &$invalidatedUserIds
    ): void {
        $activityService->upsertAggregates($rows);

        foreach ($rows as $row) {
            $invalidatedUserIds[(int) $row->user_id] = true;
        }
    }
}
