<?php

namespace App\Service;

use App\Entity\Repas;
use App\Entity\User;
use App\Repository\RepasRepository;

final class MealStatsService
{
    public function __construct(private PeriodService $periodService)
    {
    }

    /**
     * @return array{
     *     meals: array<int, Repas>,
     *     mealGroups: array<int, array{label: string, meals: array<int, Repas>}>,
     *     topMeals: array<int, Repas>,
     *     mealChart: array{labels: array<int, string>, values: array<int, int>}
     * }
     */
    public function buildIndexData(User $user, string $period, RepasRepository $repasRepository): array
    {
        [$start, $end] = $this->periodService->resolvePeriod($period);

        $meals = $repasRepository->findByUserAndPeriod($user, $start, $end);
        $mealGroups = $this->groupMealsByDate($meals);
        $topMeals = $this->topMeals($meals);
        $mealChart = $this->buildMealChart($meals, $start, $end);

        return [
            'meals' => $meals,
            'mealGroups' => $mealGroups,
            'topMeals' => $topMeals,
            'mealChart' => $mealChart,
        ];
    }

    /**
     * @param array<int, Repas> $meals
     * @return array<int, Repas>
     */
    public function filterByMealType(array $meals, ?string $mealType): array
    {
        if ($mealType === null) {
            return $meals;
        }

        return array_values(array_filter(
            $meals,
            static fn (Repas $meal): bool => $meal->getTypeRepas() === $mealType
        ));
    }

    /**
     * @param array<int, Repas> $meals
     */
    public function sumCalories(array $meals): int
    {
        return array_sum(array_map(
            static fn (Repas $meal): int => (int) $meal->getTotalCalories(),
            $meals
        ));
    }

    /**
     * @param array<int, Repas> $meals
     * @return array<int, Repas>
     */
    private function topMeals(array $meals): array
    {
        $topMeals = $meals;
        usort($topMeals, static fn (Repas $a, Repas $b) => $b->getTotalCalories() <=> $a->getTotalCalories());

        return array_slice($topMeals, 0, 3);
    }

    /**
     * @param array<int, Repas> $meals
     * @return array<int, array{label: string, meals: array<int, Repas>}>
     */
    private function groupMealsByDate(array $meals): array
    {
        $groups = [];
        foreach ($meals as $meal) {
            $date = $meal->getDateRepas()?->format('Y-m-d') ?? 'unknown';
            if (!isset($groups[$date])) {
                $groups[$date] = [
                    'label' => $meal->getDateRepas()?->format('d/m/Y') ?? 'Date inconnue',
                    'meals' => [],
                ];
            }
            $groups[$date]['meals'][] = $meal;
        }

        return array_values($groups);
    }

    /**
     * @param array<int, Repas> $meals
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function buildMealChart(array $meals, ?\DateTimeImmutable $start, ?\DateTimeImmutable $end): array
    {
        if ($start === null || $end === null) {
            $end = new \DateTimeImmutable('today');
            $start = $end->modify('-6 days');
        }

        $totalsByDate = [];
        foreach ($meals as $meal) {
            $dateKey = $meal->getDateRepas()?->format('Y-m-d');
            if ($dateKey === null) {
                continue;
            }
            $totalsByDate[$dateKey] = ($totalsByDate[$dateKey] ?? 0) + (int) $meal->getTotalCalories();
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
}
