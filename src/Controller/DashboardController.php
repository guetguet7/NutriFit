<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ActiviteRepository;
use App\Repository\RepasRepository;
use App\Service\DashboardStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard_show')]
    public function show(Request $request, RepasRepository $repasRepository, ActiviteRepository $activiteRepository, DashboardStatsService $statsService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $period = $request->query->getString('period', 'day');
        $period = in_array($period, ['day', 'week', 'month', 'all'], true) ? $period : 'day';

        $mealType = $request->query->getString('mealType', '');
        $mealType = in_array($mealType, ['petit_dej', 'dejeuner', 'diner', 'collation'], true) ? $mealType : null;

        $stats = $statsService->build($user, $period, $mealType, $repasRepository, $activiteRepository);

        return $this->render('dashboard/show.html.twig', [
            'targetKcal' => $stats['targetKcal'],
            'consumedPeriod' => $stats['consumedPeriod'],
            'burnedPeriod' => $stats['burnedPeriod'],
            'remaining' => $stats['remaining'],
            'progress' => $stats['progress'],
            'netPeriod' => $stats['netPeriod'],
            'series' => [],
            'lastMeals' => $stats['lastMeals'],
            'lastActivities' => $stats['lastActivities'],
            'period' => $period,
            'periodLabel' => $stats['periodLabel'],
            'mealType' => $mealType,
            'mealTypeLabel' => $stats['mealTypeLabel'],
        ]);
    }
}
