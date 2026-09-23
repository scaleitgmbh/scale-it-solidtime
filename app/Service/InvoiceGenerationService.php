<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\InvoiceEntry;
use App\Models\InvoiceRecipient;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Carbon;

class InvoiceGenerationService
{
    /**
     * Proposes invoice entries built from tracked, billable, not-yet-invoiced time entries of the
     * recipient's linked client in the given date range, grouped by project (optionally by project + task).
     *
     * @return array<int, array{name: string, description: string|null, unit_price: int, quantity: float, time_entry_ids: array<int, string>}>
     */
    public function previewEntries(Organization $organization, InvoiceRecipient $recipient, Carbon $start, Carbon $end, bool $groupByTask = false): array
    {
        if ($recipient->client_id === null) {
            return [];
        }

        $groupBy = $groupByTask ? ['project_id', 'task_id'] : ['project_id'];

        $rows = TimeEntry::query()
            ->where('organization_id', '=', $organization->getKey())
            ->where('client_id', '=', $recipient->client_id)
            ->where('billable', '=', true)
            ->whereNull('invoice_entry_id')
            ->whereNotNull('end')
            ->where('start', '>=', $start)
            ->where('start', '<', $end)
            ->selectRaw(
                'project_id, '.
                ($groupByTask ? 'task_id, ' : '').
                'round(sum(extract(epoch from ("end" - start)))) as seconds, '.
                'round(sum(extract(epoch from ("end" - start)) * (coalesce(billable_rate, 0)::float / 3600))) as cost, '.
                'array_agg(id) as time_entry_ids'
            )
            ->groupBy($groupBy)
            ->get();

        $projects = Project::query()
            ->whereIn('id', $rows->pluck('project_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        $tasks = $groupByTask
            ? Task::query()
                ->whereIn('id', $rows->pluck('task_id')->filter()->unique()->values())
                ->get()
                ->keyBy('id')
            : collect();

        $entries = [];
        foreach ($rows as $row) {
            // seconds/cost/time_entry_ids are raw selectRaw() aliases, not real TimeEntry columns.
            $seconds = (int) $row->seconds; // @phpstan-ignore property.notFound
            if ($seconds <= 0) {
                continue;
            }
            $hours = BigDecimal::of((string) $seconds)->dividedBy(3600, 4, RoundingMode::HALF_UP);
            $costCents = BigDecimal::of((string) (int) $row->cost); // @phpstan-ignore property.notFound
            $unitPrice = $hours->isZero() ? 0 : $costCents->dividedBy($hours, 0, RoundingMode::HALF_UP)->toInt();

            $project = $row->project_id !== null ? $projects->get($row->project_id) : null;
            $task = $groupByTask && $row->task_id !== null ? $tasks->get($row->task_id) : null;

            $entries[] = [
                'name' => $project !== null ? $project->name : 'Unassigned time',
                'description' => $task?->name,
                'unit_price' => $unitPrice,
                'quantity' => (float) (string) $hours,
                'time_entry_ids' => $this->parsePostgresUuidArray($row->time_entry_ids), // @phpstan-ignore property.notFound
            ];
        }

        return $entries;
    }

    /**
     * Guarded claim: only claims time entries that belong to the organization and are not yet claimed
     * by another invoice entry. Silently drops entries claimed elsewhere in the meantime.
     *
     * @param  array<int, string>  $timeEntryIds
     */
    public function claimTimeEntries(InvoiceEntry $entry, array $timeEntryIds, Organization $organization): void
    {
        if ($timeEntryIds === []) {
            return;
        }

        TimeEntry::query()
            ->where('organization_id', '=', $organization->getKey())
            ->whereIn('id', $timeEntryIds)
            ->whereNull('invoice_entry_id')
            ->update(['invoice_entry_id' => $entry->getKey()]);
    }

    /**
     * @return array<int, string>
     */
    private function parsePostgresUuidArray(?string $value): array
    {
        if ($value === null) {
            return [];
        }
        $trimmed = trim($value, '{}');
        if ($trimmed === '') {
            return [];
        }

        return explode(',', $trimmed);
    }
}
