<?php

namespace App\Service;

final class PeriodService
{
    /**
     * @return array{0: \DateTimeImmutable|null, 1: \DateTimeImmutable|null}
     */
    public function resolvePeriod(string $period): array
    {
        $now = new \DateTimeImmutable('now');

        return match ($period) {
            'day' => [
                $now->setTime(0, 0),
                $now->setTime(23, 59, 59),
            ],
            'week' => [
                $now->modify('monday this week')->setTime(0, 0),
                $now->modify('sunday this week')->setTime(23, 59, 59),
            ],
            'month' => [
                $now->modify('first day of this month')->setTime(0, 0),
                $now->modify('last day of this month')->setTime(23, 59, 59),
            ],
            default => [null, null],
        };
    }

    public function resolveTargetForPeriod(int $targetKcal, ?\DateTimeImmutable $start, ?\DateTimeImmutable $end, string $period): int
    {
        if ($period === 'all' || $start === null || $end === null) {
            return $targetKcal;
        }

        $days = (int) $start->diff($end)->days + 1;
        $days = $days > 0 ? $days : 1;

        return $targetKcal * $days;
    }

    public function labelForPeriod(string $period): string
    {
        return match ($period) {
            'week' => 'Semaine',
            'month' => 'Mois',
            'all' => 'Tous',
            default => 'Auj.',
        };
    }
}
