<?php

namespace App\Service;

use App\Entity\Activite;
use App\Entity\User;
use App\Repository\ActiviteRepository;

final class ActivityStatsService
{
    public function __construct(private PeriodService $periodService)
    {
    }

    /**
     * @return array{
     *     activities: array<int, Activite>,
     *     activityGroups: array<int, array{label: string, activities: array<int, Activite>}>,
     *     activityChart: array{labels: array<int, string>, values: array<int, int>},
     *     totalBurnedToday: int,
     *     totalMinutesWeek: int,
     *     topActivity: string,
     *     topActivityCount: int,
     *     topActivities: array<int, array{type: string, percent: int}>
     * }
     */
    public function buildIndexData(User $user, string $period, ActiviteRepository $activiteRepository): array
    {
        [$start, $end] = $this->periodService->resolvePeriod($period);

        $activities = $activiteRepository->findByUserAndPeriod($user, $start, $end);
        $activityGroups = $this->groupActivitiesByDate($activities);
        $activityChart = $this->buildActivityChart($activities, $start, $end);

        [$todayStart, $todayEnd] = $this->periodService->resolvePeriod('day');
        $activitiesToday = $activiteRepository->findByUserAndPeriod($user, $todayStart, $todayEnd);
        $totalBurnedToday = $this->sumCalories($activitiesToday);

        [$weekStart, $weekEnd] = $this->periodService->resolvePeriod('week');
        $activitiesWeek = $activiteRepository->findByUserAndPeriod($user, $weekStart, $weekEnd);
        $totalMinutesWeek = $this->sumMinutes($activitiesWeek);

        [$topActivity, $topActivityCount] = $this->topActivityByCount($activitiesWeek);
        $topActivities = $this->topActivitiesByMinutes($activities);

        return [
            'activities' => $activities,
            'activityGroups' => $activityGroups,
            'activityChart' => $activityChart,
            'totalBurnedToday' => $totalBurnedToday,
            'totalMinutesWeek' => $totalMinutesWeek,
            'topActivity' => $topActivity,
            'topActivityCount' => $topActivityCount,
            'topActivities' => $topActivities,
        ];
    }

    /**
     * @param array<int, Activite> $activities
     */
    public function sumCalories(array $activities): int
    {
        return array_sum(array_map(
            static fn (Activite $activity): int => (int) $activity->getCaloriesBrulees(),
            $activities
        ));
    }

    /**
     * @param array<int, Activite> $activities
     */
    private function sumMinutes(array $activities): int
    {
        return array_sum(array_map(
            static fn (Activite $activity): int => (int) $activity->getDureeMin(),
            $activities
        ));
    }

    /**
     * @param array<int, Activite> $activities
     * @return array{0: string, 1: int}
     */
    private function topActivityByCount(array $activities): array
    {
        $countByType = [];
        foreach ($activities as $activity) {
            $type = $activity->getType() ?? 'Autre';
            $countByType[$type] = ($countByType[$type] ?? 0) + 1;
        }
        arsort($countByType);
        $topActivity = array_key_first($countByType) ?? 'Aucune';
        $topActivityCount = $topActivity !== 'Aucune' ? (int) $countByType[$topActivity] : 0;

        return [$topActivity, $topActivityCount];
    }

    /**
     * @param array<int, Activite> $activities
     * @return array<int, array{type: string, percent: int}>
     */
    private function topActivitiesByMinutes(array $activities): array
    {
        $minutesByType = [];
        $totalMinutes = 0;
        foreach ($activities as $activity) {
            $minutes = (int) $activity->getDureeMin();
            $type = $activity->getType() ?? 'Autre';
            $minutesByType[$type] = ($minutesByType[$type] ?? 0) + $minutes;
            $totalMinutes += $minutes;
        }
        arsort($minutesByType);
        $topActivities = [];
        foreach (array_slice($minutesByType, 0, 3, true) as $type => $minutes) {
            $percent = $totalMinutes > 0 ? (int) round(($minutes / $totalMinutes) * 100) : 0;
            $topActivities[] = [
                'type' => $type,
                'percent' => $percent,
            ];
        }

        return $topActivities;
    }

    /**
     * @param array<int, Activite> $activities
     * @return array<int, array{label: string, activities: array<int, Activite>}>
     */
    private function groupActivitiesByDate(array $activities): array
    {
        $groups = [];
        foreach ($activities as $activity) {
            $date = $activity->getDateActivite()?->format('Y-m-d') ?? 'unknown';
            if (!isset($groups[$date])) {
                $groups[$date] = [
                    'label' => $activity->getDateActivite()?->format('d/m/Y') ?? 'Date inconnue',
                    'activities' => [],
                ];
            }
            $groups[$date]['activities'][] = $activity;
        }

        return array_values($groups);
    }

    /**
     * @param array<int, Activite> $activities
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function buildActivityChart(array $activities, ?\DateTimeImmutable $start, ?\DateTimeImmutable $end): array
    {
        if ($start === null || $end === null) {
            $end = new \DateTimeImmutable('today');
            $start = $end->modify('-6 days');
        }

        $totalsByDate = [];
        foreach ($activities as $activity) {
            $dateKey = $activity->getDateActivite()?->format('Y-m-d');
            if ($dateKey === null) {
                continue;
            }
            $totalsByDate[$dateKey] = ($totalsByDate[$dateKey] ?? 0) + (int) $activity->getCaloriesBrulees();
        }

        $labels = [];
        $values = [];
        $dayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1 day'));
        foreach ($period as $day) {
            $key = $day->format('Y-m-d');
            $labels[] = $dayLabels[(int) $day->format('N') - 1];
            $values[] = (int) ($totalsByDate[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    public function estimateCalories(string $type, string $intensite, int $dureeMin): int
    {
        $baseRates = [
            'course' => 10,
            'marche' => 4,
            'velo' => 6,
            'natation' => 9,
        ];
        $multipliers = [
            'faible' => 0.8,
            'moyenne' => 1.0,
            'forte' => 1.2,
        ];

        $rate = $baseRates[$type] ?? 5;
        $multiplier = $multipliers[$intensite] ?? 1.0;

        return (int) round($rate * $multiplier * $dureeMin);
    }
}
