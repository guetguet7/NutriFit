<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use App\Service\ActivityStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ActiviteController extends AbstractController
{
    #[Route('/activites', name: 'activite')]
    public function index(Request $request, ActiviteRepository $activiteRepository, ActivityStatsService $statsService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $period = $request->query->getString('period', 'week');
        $period = in_array($period, ['day', 'week', 'month', 'all'], true) ? $period : 'week';

        $stats = $statsService->buildIndexData($user, $period, $activiteRepository);

        return $this->render('activite/index.html.twig', [
            'totalBurnedToday' => $stats['totalBurnedToday'],
            'totalMinutesWeek' => $stats['totalMinutesWeek'],
            'topActivity' => $stats['topActivity'],
            'topActivityCount' => $stats['topActivityCount'],
            'activities' => $stats['activities'],
            'activityGroups' => $stats['activityGroups'],
            'period' => $period,
            'selectedActivity' => null,
            'activityChart' => $stats['activityChart'],
            'topActivities' => $stats['topActivities'],
        ]);
    }

    #[Route('/activites/nouveau', name: 'activite_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityStatsService $statsService, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $estimatedCalories = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_activite', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $allowedTypes = ['course', 'marche', 'velo', 'natation'];
            $allowedCaloriesModes = ['auto', 'manual'];
            $allowedIntensities = ['faible', 'moyenne', 'forte'];

            $type = $request->request->getString('type', 'marche');
            $type = in_array($type, $allowedTypes, true) ? $type : 'marche';
            $dateRaw = $request->request->getString('dateActivite', '');
            $dateActivite = $this->parseActivityDate($dateRaw);
            $dureeMin = (int) $request->request->getString('dureeMin', '0');
            $caloriesMode = $request->request->getString('caloriesMode', 'auto');
            $caloriesMode = in_array($caloriesMode, $allowedCaloriesModes, true) ? $caloriesMode : 'auto';
            $intensite = $request->request->getString('intensite', 'moyenne');
            $intensite = in_array($intensite, $allowedIntensities, true) ? $intensite : 'moyenne';

            if ($dureeMin <= 0) {
                $this->addFlash('warning', 'La durée doit être supérieure à 0.');
            } elseif (!$dateActivite instanceof \DateTimeImmutable) {
                $this->addFlash('warning', 'La date de l\'activité est invalide.');
            } else {
                if ($caloriesMode === 'manual') {
                    $caloriesBrulees = (int) $request->request->getString('caloriesBrulees', '0');
                } else {
                    $caloriesBrulees = $statsService->estimateCalories($type, $intensite, $dureeMin);
                }

                if ($caloriesBrulees <= 0) {
                    $this->addFlash('warning', 'Les calories brûlées doivent être supérieures à 0.');
                } else {
                    $activite = new Activite();
                    $activite->setUtilisateur($user);
                    $activite->setType($type);
                    $activite->setDureeMin($dureeMin);
                    $activite->setCaloriesBrulees($caloriesBrulees);
                    $activite->setDateActivite($dateActivite);

                    $violations = $validator->validate($activite);
                    if (count($violations) > 0) {
                        $this->addFlash('warning', (string) $violations->get(0)->getMessage());
                    } else {
                        $entityManager->persist($activite);
                        $entityManager->flush();
                        $this->addFlash('success', 'Activité enregistrée.');

                        return $this->redirectToRoute('activite');
                    }
                }
            }

            $estimatedCalories = $statsService->estimateCalories($type, $intensite, $dureeMin);
        }

        return $this->render('activite/new.html.twig', [
            'estimatedCalories' => $estimatedCalories,
        ]);
    }

    #[Route('/activites/{id}/modifier', name: 'activite_edit', requirements: ['id' => '\d+'])]
    public function edit(Activite $activite, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($activite->getUtilisateur()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_activite_' . $activite->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $allowedTypes = ['course', 'marche', 'velo', 'natation'];
            $type = $request->request->getString('type', $activite->getType() ?? 'marche');
            $type = in_array($type, $allowedTypes, true) ? $type : ($activite->getType() ?? 'marche');
            $dateRaw = $request->request->getString('dateActivite', '');
            $dureeMin = (int) $request->request->getString('dureeMin', (string) ($activite->getDureeMin() ?? 0));
            $caloriesBrulees = (int) $request->request->getString('caloriesBrulees', (string) ($activite->getCaloriesBrulees() ?? 0));

            if ($dureeMin <= 0) {
                $this->addFlash('warning', 'La durée doit être supérieure à 0.');
            } elseif ($caloriesBrulees <= 0) {
                $this->addFlash('warning', 'Les calories brûlées doivent être supérieures à 0.');
            } else {
                $dateActivite = $this->parseActivityDate($dateRaw);
                if (!$dateActivite instanceof \DateTimeImmutable) {
                    $this->addFlash('warning', 'La date de l\'activité est invalide.');

                    return $this->redirectToRoute('activite_edit', ['id' => $activite->getId()]);
                }

                $activite->setType($type);
                $activite->setDateActivite($dateActivite);
                $activite->setDureeMin($dureeMin);
                $activite->setCaloriesBrulees($caloriesBrulees);

                $violations = $validator->validate($activite);
                if (count($violations) > 0) {
                    $this->addFlash('warning', (string) $violations->get(0)->getMessage());
                } else {
                    $entityManager->flush();
                    $this->addFlash('success', 'Activité modifiée.');

                    return $this->redirectToRoute('activite');
                }
            }
        }

        return $this->render('activite/edit.html.twig', [
            'activite' => $activite,
        ]);
    }

    #[Route('/activites/{id}/supprimer', name: 'activite_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Activite $activite, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($activite->getUtilisateur()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_activite_' . $activite->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($activite);
        $entityManager->flush();
        $this->addFlash('success', 'Activité supprimée.');

        return $this->redirectToRoute('activite');
    }

    private function parseActivityDate(string $dateRaw): ?\DateTimeImmutable
    {
        $timezone = new \DateTimeZone((string) date_default_timezone_get());
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $format) {
            $parsedDate = \DateTimeImmutable::createFromFormat($format, $dateRaw, $timezone);
            if ($parsedDate instanceof \DateTimeImmutable) {
                return $parsedDate;
            }
        }
        return null;
    }
}
