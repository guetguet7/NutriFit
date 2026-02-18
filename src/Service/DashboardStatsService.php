<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ActiviteRepository;
use App\Repository\RepasRepository;

final class DashboardStatsService
{
    public function __construct(
        private PeriodService $periodService,
        private MealStatsService $mealStatsService,
        private ActivityStatsService $activityStatsService,
        private CalorieCalculator $calorieCalculator
    ) {
    }

    /**
     * @return array{
     *     targetKcal: int,
     *     consumedPeriod: int,
     *     burnedPeriod: int,
     *     remaining: int,
     *     progress: int,
     *     netPeriod: int,
     *     lastMeals: array<int, \App\Entity\Repas>,
     *     lastActivities: array<int, \App\Entity\Activite>,
     *     periodLabel: string,
     *     mealTypeLabel: string|null
     * }
     */
    public function build(
        User $user,
        string $period,
        ?string $mealType,
        RepasRepository $repasRepository,
        ActiviteRepository $activiteRepository
    ): array {
        [$start, $end] = $this->periodService->resolvePeriod($period);
        $periodLabel = $this->periodService->labelForPeriod($period);

        $profil = $user->getProfilUtilisateur();
        $targetKcal = $profil ? $this->calorieCalculator->getTargetKcal($profil) : 2000;

        // Récupérer les repas de la période et filtrer par type de repas
        $mealsPeriod = $repasRepository->findByUserAndPeriod($user, $start, $end);
        $mealsPeriod = $this->mealStatsService->filterByMealType($mealsPeriod, $mealType);
        $consumedPeriod = $this->mealStatsService->sumCalories($mealsPeriod);

        $activitiesPeriod = $activiteRepository->findByUserAndPeriod($user, $start, $end);
        $burnedPeriod = $this->activityStatsService->sumCalories($activitiesPeriod);

        $netPeriod = $consumedPeriod - $burnedPeriod;
        $targetPeriod = $this->periodService->resolveTargetForPeriod($targetKcal, $start, $end, $period);
        $remaining = $targetPeriod - $netPeriod;
        $progress = $targetPeriod > 0 ? (int) min(100, round(($consumedPeriod / $targetPeriod) * 100)) : 0;

        $lastMeals = $repasRepository->findBy(
            ['utilisateur' => $user],
            ['dateRepas' => 'DESC'],
            5
        );
        $lastMeals = $this->mealStatsService->filterByMealType($lastMeals, $mealType);

        $lastActivities = $activiteRepository->findBy(
            ['utilisateur' => $user],
            ['dateActivite' => 'DESC'],
            5
        );

        $mealTypeLabel = match ($mealType) {
            'petit_dej' => 'Petit-dej',
            'dejeuner' => 'Déjeuner',
            'diner' => 'Dîner',
            'collation' => 'Collation',
            default => null,
        };

        return [
            'targetKcal' => $targetKcal,
            'consumedPeriod' => $consumedPeriod,
            'burnedPeriod' => $burnedPeriod,
            'remaining' => $remaining,
            'progress' => $progress,
            'netPeriod' => $netPeriod,
            'lastMeals' => $lastMeals,
            'lastActivities' => $lastActivities,
            'periodLabel' => $periodLabel,
            'mealTypeLabel' => $mealTypeLabel,
        ];
    }
}
