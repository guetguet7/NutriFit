<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use App\Security\Voter\ActiviteVoter;
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
        $formErrors = [];
        $fieldErrors = [];
        $addError = static function (array &$errors, array &$fields, string $message, ?string $field = null): void {
            $errors[] = $message;
            if ($field !== null) {
                $fields[$field][] = $message;
            }
        };

        $type = 'marche';
        $dateActiviteValue = (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i');
        $dureeMinValue = '';
        $caloriesMode = 'auto';
        $intensite = 'moyenne';
        $niveauValue = 'debutant';
        $caloriesBruleesValue = '';
        $distanceKmValue = '';
        $pasValue = '';

        if ($request->isMethod('POST')) {
            $hasInputErrors = false;
            if (!$this->isCsrfTokenValid('create_activite', $request->request->getString('_token'))) {
                $addError($formErrors, $fieldErrors, 'La session a expiré. Rechargez la page puis réessayez.', 'global');
                $hasInputErrors = true;
            }

            $allowedTypes = ['course', 'marche', 'velo', 'natation'];
            $allowedCaloriesModes = ['auto', 'manual'];
            $allowedIntensities = ['faible', 'moyenne', 'forte'];
            $allowedLevels = ['debutant', 'intermediaire', 'avance'];

            $typeRaw = $request->request->getString('type', $type);
            $type = in_array($typeRaw, $allowedTypes, true) ? $typeRaw : 'marche';
            if (!in_array($typeRaw, $allowedTypes, true)) {
                $addError($formErrors, $fieldErrors, 'Le type d\'activité est invalide.', 'type');
            }

            $dateRaw = $request->request->getString('dateActivite', $dateActiviteValue);
            $dateActiviteValue = $dateRaw;
            $dateActivite = $dateRaw !== '' ? $this->parseActivityDate($dateRaw) : null;

            $dureeRaw = trim($request->request->getString('dureeMin', $dureeMinValue));
            $dureeMinValue = $dureeRaw;
            $dureeMin = $dureeRaw !== '' ? (int) $dureeRaw : 0;

            $caloriesModeRaw = $request->request->getString('caloriesMode', $caloriesMode);
            $caloriesMode = in_array($caloriesModeRaw, $allowedCaloriesModes, true) ? $caloriesModeRaw : 'auto';
            if (!in_array($caloriesModeRaw, $allowedCaloriesModes, true)) {
                $addError($formErrors, $fieldErrors, 'Le mode calories est invalide.', 'caloriesMode');
            }

            $intensiteRaw = $request->request->getString('intensite', $intensite);
            $intensite = in_array($intensiteRaw, $allowedIntensities, true) ? $intensiteRaw : 'moyenne';
            if (!in_array($intensiteRaw, $allowedIntensities, true)) {
                $addError($formErrors, $fieldErrors, 'L\'intensité est invalide.', 'intensite');
            }

            $niveauRaw = $request->request->getString('niveau', $niveauValue);
            $niveauValue = in_array($niveauRaw, $allowedLevels, true) ? $niveauRaw : 'debutant';

            $distanceKmValue = trim($request->request->getString('distanceKm', ''));
            $pasValue = trim($request->request->getString('pas', ''));

            $caloriesRaw = trim($request->request->getString('caloriesBrulees', $caloriesBruleesValue));
            $caloriesBruleesValue = $caloriesRaw;

            if ($dureeRaw === '') {
                $addError($formErrors, $fieldErrors, 'La durée est obligatoire.', 'dureeMin');
                $hasInputErrors = true;
            } elseif ($dureeMin <= 0) {
                $addError($formErrors, $fieldErrors, 'La durée doit être supérieure à 0.', 'dureeMin');
                $hasInputErrors = true;
            }

            if ($dateRaw === '') {
                $addError($formErrors, $fieldErrors, 'La date de l\'activité est obligatoire.', 'dateActivite');
                $hasInputErrors = true;
            } elseif (!$dateActivite instanceof \DateTimeImmutable) {
                $addError($formErrors, $fieldErrors, 'La date de l\'activité est invalide.', 'dateActivite');
                $hasInputErrors = true;
            }

            if ($caloriesMode === 'manual') {
                if ($caloriesRaw === '') {
                    // Fallback pragmatique: si le mode manuel est coché sans valeur,
                    // on évite de bloquer l'enregistrement et on utilise l'estimation.
                    $caloriesBrulees = $dureeMin > 0
                        ? $statsService->estimateCalories($type, $intensite, $dureeMin)
                        : 0;
                    $caloriesMode = 'auto';
                    $caloriesBruleesValue = '';
                } else {
                    $caloriesBrulees = (int) $caloriesRaw;
                    if ($caloriesBrulees <= 0) {
                        $addError($formErrors, $fieldErrors, 'Les calories brûlées doivent être supérieures à 0.', 'caloriesBrulees');
                        $hasInputErrors = true;
                    }
                }
            } else {
                $caloriesBrulees = $dureeMin > 0
                    ? $statsService->estimateCalories($type, $intensite, $dureeMin)
                    : 0;
                $caloriesBruleesValue = '';
            }

            if (!$hasInputErrors && $dateActivite instanceof \DateTimeImmutable) {
                $activite = new Activite();
                $activite->setUtilisateur($user);
                $activite->setType($type);
                $activite->setDureeMin($dureeMin);
                $activite->setCaloriesBrulees($caloriesBrulees);
                $activite->setDateActivite($dateActivite);

                $violations = $validator->validate($activite);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $propertyPath = (string) $violation->getPropertyPath();
                        $field = match ($propertyPath) {
                            'dateActivite' => 'dateActivite',
                            'dureeMin' => 'dureeMin',
                            'caloriesBrulees' => 'caloriesBrulees',
                            'type' => 'type',
                            default => 'global',
                        };
                        $addError($formErrors, $fieldErrors, (string) $violation->getMessage(), $field);
                    }
                } else {
                    $entityManager->persist($activite);
                    $entityManager->flush();
                    $this->addFlash('success', 'Activité enregistrée.');

                    return $this->redirectToRoute('activite');
                }
            }

            $estimatedCalories = $dureeMin > 0
                ? $statsService->estimateCalories($type, $intensite, $dureeMin)
                : 0;
        }

        $statusCode = $request->isMethod('POST') && count($formErrors) > 0
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

        return $this->render('activite/new.html.twig', [
            'estimatedCalories' => $estimatedCalories,
            'formErrors' => $formErrors,
            'fieldErrors' => $fieldErrors,
            'typeValue' => $type,
            'dateActiviteValue' => $dateActiviteValue,
            'dureeMinValue' => $dureeMinValue,
            'caloriesModeValue' => $caloriesMode,
            'intensiteValue' => $intensite,
            'niveauValue' => $niveauValue,
            'caloriesBruleesValue' => $caloriesBruleesValue,
            'distanceKmValue' => $distanceKmValue,
            'pasValue' => $pasValue,
        ], new Response('', $statusCode));
    }

    #[Route('/activites/{id}/modifier', name: 'activite_edit', requirements: ['id' => '\d+'])]
    public function edit(Activite $activite, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted(ActiviteVoter::EDIT, $activite);

        $formErrors = [];
        $fieldErrors = [];
        $typeValue = $activite->getType() ?? 'marche';
        $dateActiviteValue = $activite->getDateActivite()?->format('Y-m-d\TH:i') ?? '';
        $dureeMinValue = (string) ($activite->getDureeMin() ?? '');
        $caloriesBruleesValue = (string) ($activite->getCaloriesBrulees() ?? '');

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_activite_' . $activite->getId(), $request->request->getString('_token'))) {
                $fieldErrors['global'][] = 'La session a expiré. Rechargez la page puis réessayez.';
            }

            $addError = static function (array &$errors, array &$fields, string $message, ?string $field = null): void {
                $errors[] = $message;
                if ($field !== null) {
                    $fields[$field][] = $message;
                }
            };
            $allowedTypes = ['course', 'marche', 'velo', 'natation'];
            $typeRaw = $request->request->getString('type', $typeValue);
            $type = in_array($typeRaw, $allowedTypes, true) ? $typeRaw : ($activite->getType() ?? 'marche');
            $typeValue = $type;
            if (!in_array($typeRaw, $allowedTypes, true)) {
                $addError($formErrors, $fieldErrors, 'Le type d\'activité est invalide.', 'type');
            }
            $dateRaw = $request->request->getString('dateActivite', $dateActiviteValue);
            $dateActiviteValue = $dateRaw;
            $dateActivite = $dateRaw !== '' ? $this->parseActivityDate($dateRaw) : null;
            $dureeRaw = trim($request->request->getString('dureeMin', $dureeMinValue));
            $dureeMinValue = $dureeRaw;
            $dureeMin = $dureeRaw !== '' ? (int) $dureeRaw : 0;
            $caloriesRaw = trim($request->request->getString('caloriesBrulees', $caloriesBruleesValue));
            $caloriesBruleesValue = $caloriesRaw;
            $caloriesBrulees = $caloriesRaw !== '' ? (int) $caloriesRaw : 0;
            $hasInputErrors = isset($fieldErrors['global']);

            if ($dureeRaw === '') {
                $addError($formErrors, $fieldErrors, 'La durée est obligatoire.', 'dureeMin');
                $hasInputErrors = true;
            } elseif ($dureeMin <= 0) {
                $addError($formErrors, $fieldErrors, 'La durée doit être supérieure à 0.', 'dureeMin');
                $hasInputErrors = true;
            }

            if ($caloriesRaw === '') {
                $addError($formErrors, $fieldErrors, 'Les calories brûlées sont obligatoires.', 'caloriesBrulees');
                $hasInputErrors = true;
            } elseif ($caloriesBrulees <= 0) {
                $addError($formErrors, $fieldErrors, 'Les calories brûlées doivent être supérieures à 0.', 'caloriesBrulees');
                $hasInputErrors = true;
            }

            if ($dateRaw === '') {
                $addError($formErrors, $fieldErrors, 'La date de l\'activité est obligatoire.', 'dateActivite');
                $hasInputErrors = true;
            } elseif (!$dateActivite instanceof \DateTimeImmutable) {
                $addError($formErrors, $fieldErrors, 'La date de l\'activité est invalide.', 'dateActivite');
                $hasInputErrors = true;
            }

            if (!$hasInputErrors && $dateActivite instanceof \DateTimeImmutable) {
                $activite->setType($type);
                $activite->setDateActivite($dateActivite);
                $activite->setDureeMin($dureeMin);
                $activite->setCaloriesBrulees($caloriesBrulees);

                $violations = $validator->validate($activite);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $propertyPath = (string) $violation->getPropertyPath();
                        $field = match ($propertyPath) {
                            'dateActivite' => 'dateActivite',
                            'dureeMin' => 'dureeMin',
                            'caloriesBrulees' => 'caloriesBrulees',
                            'type' => 'type',
                            default => 'global',
                        };
                        $addError($formErrors, $fieldErrors, (string) $violation->getMessage(), $field);
                    }
                } else {
                    $entityManager->flush();
                    $this->addFlash('success', 'Activité modifiée.');

                    return $this->redirectToRoute('activite');
                }
            }
        }

        $statusCode = $request->isMethod('POST') && count($formErrors) > 0
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

        return $this->render('activite/edit.html.twig', [
            'activite' => $activite,
            'formErrors' => $formErrors,
            'fieldErrors' => $fieldErrors,
            'typeValue' => $typeValue,
            'dateActiviteValue' => $dateActiviteValue,
            'dureeMinValue' => $dureeMinValue,
            'caloriesBruleesValue' => $caloriesBruleesValue,
        ], new Response('', $statusCode));
    }

    #[Route('/activites/{id}/supprimer', name: 'activite_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Activite $activite, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted(ActiviteVoter::DELETE, $activite);

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
        $dateRaw = trim($dateRaw);
        if ($dateRaw === '') {
            return null;
        }

        $timezone = new \DateTimeZone((string) date_default_timezone_get());
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:s.u'] as $format) {
            $parsedDate = \DateTimeImmutable::createFromFormat($format, $dateRaw, $timezone);
            if ($parsedDate instanceof \DateTimeImmutable) {
                return $parsedDate;
            }
        }

        try {
            return new \DateTimeImmutable($dateRaw, $timezone);
        } catch (\Exception) {
            return null;
        }
    }
}
