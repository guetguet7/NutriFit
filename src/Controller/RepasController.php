<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ElementRepas;
use App\Entity\Recette;
use App\Entity\Repas;
use App\Repository\RepasRepository;
use App\Service\CalorieCalculator;
use App\Service\MealStatsService;
use App\Service\SpoonacularClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RepasController extends AbstractController
{
    #[Route('/repas', name: 'repas')]
    public function index(Request $request, RepasRepository $repasRepository, MealStatsService $mealStatsService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Récupération des repas et calcul des stats
        $period = $request->query->getString('period', 'week');
        $period = in_array($period, ['day', 'week', 'month', 'all'], true) ? $period : 'week';

        // Calcule des calories cibles pour le repas
        $stats = $mealStatsService->buildIndexData($user, $period, $repasRepository);
        $mealGroups = $stats['mealGroups'];

        return $this->render('repas/index.html.twig', [
            'period' => $period,
            'mealGroups' => $mealGroups,
            'topMeals' => $stats['topMeals'],
            'averageCaloriesWeek' => 0,
            'mealChart' => $stats['mealChart'],
        ]);
    }

    #[Route('/repas/{mealId}/recette/{recipeId}', name: 'repas_recipe_details', requirements: ['mealId' => '\d+', 'recipeId' => '\d+'], methods: ['GET'])]
    public function recipeDetails(Request $request, int $mealId, int $recipeId, EntityManagerInterface $entityManager, SpoonacularClient $spoonacularClient, CacheInterface $cache): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $repas = $entityManager->getRepository(Repas::class)->find($mealId);
        if (!$repas instanceof Repas) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('REPAS_VIEW', $repas);

        $recette = $entityManager->getRepository(Recette::class)->findOneBy(['spoonacularId' => $recipeId]);
        if (!$recette instanceof Recette) {
            throw $this->createNotFoundException();
        }

        $belongsToMeal = false;
        foreach ($repas->getElementsRepas() as $element) {
            if ($element->getRecette()?->getSpoonacularId() === $recipeId) {
                $belongsToMeal = true;
                break;
            }
        }
        if (!$belongsToMeal) {
            throw $this->createNotFoundException();
        }

        $detailsCacheKey = sprintf('spoonacular_recipe_details_%d', $recipeId);
        $details = $cache->get($detailsCacheKey, function (ItemInterface $item) use ($spoonacularClient, $recipeId) {
            $item->expiresAfter(86400);
            return $spoonacularClient->getRecipeDetails($recipeId);
        });

        $raw = $recette->getRawJson() ?? [];
        if (is_array($details)) {
            $raw['instructions'] = $details['instructions'] ?? ($raw['instructions'] ?? null);
            $raw['summary'] = $details['summary'] ?? ($raw['summary'] ?? null);
        }

        $instructions = is_array($raw) ? ($raw['instructions'] ?? null) : null;
        $summary = is_array($raw) ? ($raw['summary'] ?? null) : null;

        return $this->render('repas/recipe_details.html.twig', [
            'repas' => $repas,
            'recette' => $recette,
            'instructions' => $instructions,
            'summary' => $summary,
            'backPeriod' => $request->query->getString('period', 'week'),
        ]);
    }

    #[Route('/api/recipes', name: 'api_recipes', methods: ['GET'])]
    public function apiRecipes(Request $request, SpoonacularClient $spoonacularClient): JsonResponse
    {
        $query = trim($request->query->getString('q', ''));
        if ($query === '') {
            return $this->json(['results' => []]);
        }

        $min = (int) $request->query->getString('min', '0');
        $max = (int) $request->query->getString('max', '10000');

        try {
            $results = $spoonacularClient->searchRecipes($query, $min, $max);
        } catch (\Throwable) {
            return $this->json(['results' => []], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['results' => $results]);
    }

    #[Route('/repas/nouveau', name: 'repas_new')]
    public function new(Request $request, SpoonacularClient $spoonacularClient, EntityManagerInterface $entityManager, CalorieCalculator $calorieCalculator, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Calcule des calories cibles pour le repas
        $profil = $user->getProfilUtilisateur();
        $targetCalories = $profil ? $calorieCalculator->getTargetKcal($profil) : 2000;

        //
        $perMealTarget = (int) round($targetCalories / 3);
        $minCalories = (int) round($perMealTarget * 0.85);
        $maxCalories = (int) round($perMealTarget * 1.15);

        // Récupération des recettes de l'API Spoonacular
        $session = $request->getSession();
        $sessionKey = 'repas_selected_recipes';
        /** @var array<int, array{id: int, title: string, image: string|null, calories: int|null, servings: int|null, qty: float}> $selectedRecipes */
        $selectedRecipes = $session->get($sessionKey, []);

        $searchQuery = $request->query->getString('search', '');
        $spoonacularResults = [];
        if ($searchQuery !== '') {
            $spoonacularResults = $spoonacularClient->searchRecipes($searchQuery, $minCalories, $maxCalories);
        }

        $selectRecipeId = $request->query->getInt('selectRecipe');
        if ($selectRecipeId > 0) {
            foreach ($spoonacularResults as $result) {
                if (($result['id'] ?? null) === $selectRecipeId) {
                    // Une seule recette API autorisée : chaque sélection remplace la précédente.
                    $selectedRecipes = [
                        $selectRecipeId => [
                            'id' => $selectRecipeId,
                            'title' => $result['title'],
                            'image' => $result['image'] ?? null,
                            'calories' => $result['calories'] ?? 0,
                            'servings' => $result['servings'] ?? 1,
                            'qty' => 1.0,
                        ],
                    ];
                    break;
                }
            }
            $session->set($sessionKey, $selectedRecipes);
        }

        if ($selectedRecipes !== [] && $spoonacularResults !== []) {
            $translatedById = [];
            foreach ($spoonacularResults as $result) {
                if (isset($result['id'], $result['title'])) {
                    $translatedById[(int) $result['id']] = (string) $result['title'];
                }
            }
            foreach ($selectedRecipes as $recipeId => $recipe) {
                if (isset($translatedById[$recipeId])) {
                    $selectedRecipes[$recipeId]['title'] = $translatedById[$recipeId];
                }
            }
        }

        $removeRecipeId = $request->query->getInt('removeRecipe');
        if ($removeRecipeId > 0 && isset($selectedRecipes[$removeRecipeId])) {
            unset($selectedRecipes[$removeRecipeId]);
            $session->set($sessionKey, $selectedRecipes);
        }

        $selectedMealType = $request->query->getString('type', '');
        $selectedMealType = in_array($selectedMealType, ['petit_dej', 'dejeuner', 'diner', 'collation'], true) ? $selectedMealType : null;

        $totalCaloriesForView = $this->calculateSelectedRecipesTotalCalories($selectedRecipes);
        $mealDateValue = $request->request->getString('dateRepas', (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i'));
        if ($mealDateValue === '') {
            $mealDateValue = (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('save_repas', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $allowedMealTypes = ['petit_dej', 'dejeuner', 'diner', 'collation'];
            $allowedSources = ['api', 'manuel'];

            $repas = new Repas();
            $repas->setUtilisateur($user);
            $hasInputErrors = false;

            $dateRaw = $request->request->getString('dateRepas', '');
            $dateRepas = $this->parseMealDate($dateRaw);
            if (!$dateRepas instanceof \DateTimeImmutable) {
                $this->addFlash('warning', 'Date invalide: heure actuelle utilisée automatiquement.');
                $dateRepas = new \DateTimeImmutable('now');
            }
            $repas->setDateRepas($dateRepas);
            $mealDateValue = $dateRepas->format('Y-m-d\TH:i');

            $typeRepas = $request->request->getString('typeRepas', $selectedMealType ?? 'dejeuner');
            if (!in_array($typeRepas, $allowedMealTypes, true)) {
                $typeRepas = $selectedMealType ?? 'dejeuner';
                $hasInputErrors = true;
                $this->addFlash('warning', 'Le type de repas est invalide.');
            }
            $repas->setTypeRepas($typeRepas);

            $notes = $request->request->getString('note', '');
            $repas->setNotes($notes !== '' ? $notes : null);

            $modeAjout = $request->request->getString('modeAjout', 'api');
            $modeAjout = in_array($modeAjout, ['api', 'manual'], true) ? $modeAjout : 'api';
            $source = $request->request->getString('source', $modeAjout === 'api' ? 'api' : 'manuel');
            if (!in_array($source, $allowedSources, true)) {
                $source = $modeAjout === 'api' ? 'api' : 'manuel';
                $hasInputErrors = true;
                $this->addFlash('warning', 'La source du repas est invalide.');
            }
            $repas->setSource($source);

            $totalCalories = 0;

            if ($modeAjout === 'manual') {
                $manualName = trim($request->request->getString('manualName', ''));
                $manualCalories = (int) $request->request->getString('manualCalories', '0');
                $manualQty = (float) $request->request->getString('manualQty', '1');

                // En mode manuel, on n'ajoute un élément que si le nom est renseigné et que les calories et la quantité sont supérieures à 0.
                if ($manualName !== '' && $manualCalories > 0 && $manualQty > 0) {
                    $element = new ElementRepas();
                    $element->setRepas($repas);
                    $element->setLibelle($manualName);
                    $element->setQuantite((string) $manualQty);
                    $element->setUnite('portion');
                    $elementCalories = (int) round($manualCalories * $manualQty);
                    $element->setCalories($elementCalories);

                    $totalCalories += $elementCalories;
                    $repas->addElementRepas($element);
                }
            } else {
                // Les quantités peuvent être personnalisées par l'utilisateur, sinon on prend la quantité par défaut de 1 portion.
                $selectedQty = $request->request->all('selectedQty');
                foreach ($selectedRecipes as $recipeId => $recipe) {
                    $qty = isset($selectedQty[$recipeId]) ? (float) $selectedQty[$recipeId] : (float) ($recipe['qty'] ?? 1);
                    $qty = $qty > 0 ? $qty : 1.0;
                    $title = (string) ($recipe['title'] ?? 'Recette');
                    $caloriesPerServing = (int) ($recipe['calories'] ?? 0);

                    if ($title === '' || $caloriesPerServing <= 0) {
                        continue;
                    }

                    $element = new ElementRepas();
                    $element->setRepas($repas);
                    $element->setLibelle($title);
                    $element->setQuantite((string) $qty);
                    $element->setUnite('portion');
                    $elementCalories = (int) round($caloriesPerServing * $qty);
                    $element->setCalories($elementCalories);

                    $recette = $this->upsertApiRecipe($entityManager, (int) $recipeId, $recipe);
                    if ($recette instanceof Recette) {
                        $element->setRecette($recette);
                    }

                    $totalCalories += $elementCalories;
                    $repas->addElementRepas($element);
                }
            }

            if ($totalCalories <= 0) {
                $this->addFlash('warning', 'Les calories doivent être supérieures à 0.');
            } elseif ($hasInputErrors) {
                // Les erreurs sont déjà remontées via flash messages.
            } else {
                $repas->setTotalCalories($totalCalories);
                $violations = $validator->validate($repas);
                if (count($violations) > 0) {
                    $this->addFlash('warning', (string) $violations->get(0)->getMessage());
                } else {
                    $entityManager->persist($repas);
                    $entityManager->flush();

                    $session->remove($sessionKey);
                    $this->addFlash(
                        'success',
                        $modeAjout === 'api' ? 'Recette ajoutée avec succès.' : 'Repas manuel ajouté avec succès.'
                    );

                    return $this->redirectToRoute('repas');
                }
            }

            $totalCaloriesForView = $totalCalories;
        }

        return $this->render('repas/new.html.twig', [
            'targetCalories' => $targetCalories,
            'perMealTarget' => $perMealTarget,
            'minCalories' => $minCalories,
            'maxCalories' => $maxCalories,
            'searchQuery' => $searchQuery,
            'spoonacularResults' => $spoonacularResults,
            'selectedRecipes' => $selectedRecipes,
            'selectedMealType' => $selectedMealType,
            'totalCalories' => $totalCaloriesForView,
            'mealDateValue' => $mealDateValue,
        ]);
    }

    /**
     * @param array<int, array{id?: int, calories?: int|null, qty?: float|int|string|null}> $selectedRecipes
     */
    private function calculateSelectedRecipesTotalCalories(array $selectedRecipes): int
    {
        $totalCalories = 0;

        foreach ($selectedRecipes as $recipe) {
            $caloriesPerServing = (int) ($recipe['calories'] ?? 0);
            $qty = isset($recipe['qty']) ? (float) $recipe['qty'] : 1.0;
            $qty = $qty > 0 ? $qty : 1.0;

            if ($caloriesPerServing <= 0) {
                continue;
            }

            $totalCalories += (int) round($caloriesPerServing * $qty);
        }

        return $totalCalories;
    }

    #[Route('/repas/{id}/edit', name: 'repas_edit')]
    public function edit(Request $request, Repas $repas, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('REPAS_EDIT', $repas);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_repas_' . $repas->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $allowedMealTypes = ['petit_dej', 'dejeuner', 'diner', 'collation'];
            $allowedSources = ['api', 'manuel'];
            $hasInputErrors = false;

            $dateRaw = $request->request->getString('dateRepas', '');
            $dateRepas = $this->parseMealDate($dateRaw);
            if (!$dateRepas instanceof \DateTimeImmutable) {
                $hasInputErrors = true;
                $this->addFlash('warning', 'La date du repas est invalide.');
            } else {
                $repas->setDateRepas($dateRepas);
            }

            $typeRepas = $request->request->getString('typeRepas', $repas->getTypeRepas());
            if (!in_array($typeRepas, $allowedMealTypes, true)) {
                $typeRepas = $repas->getTypeRepas() ?? 'dejeuner';
                $hasInputErrors = true;
                $this->addFlash('warning', 'Le type de repas est invalide.');
            }
            $repas->setTypeRepas($typeRepas);

            $notes = $request->request->getString('note', '');
            $repas->setNotes($notes !== '' ? $notes : null);

            $source = $request->request->getString('source', $repas->getSource());
            if (!in_array($source, $allowedSources, true)) {
                $source = $repas->getSource() ?? 'manuel';
                $hasInputErrors = true;
                $this->addFlash('warning', 'La source du repas est invalide.');
            }
            $repas->setSource($source);

            $elements = $request->request->all('elements');
            if ($elements === []) {
                $totalCalories = $repas->getTotalCalories();
            } else {
                $totalCalories = 0;
                foreach ($elements as $elementData) {
                    $label = trim((string) ($elementData['label'] ?? ''));
                    $qty = (float) ($elementData['qty'] ?? 0);
                    $unit = trim((string) ($elementData['unit'] ?? ''));
                    $calories = (int) ($elementData['calories'] ?? 0);
                    $recipeId = (int) ($elementData['recipeId'] ?? 0);

                    if ($label === '' || $qty <= 0 || $calories <= 0) {
                        continue;
                    }

                    $element = new ElementRepas();
                    $element->setRepas($repas);
                    $element->setLibelle($label);
                    $element->setQuantite((string) $qty);
                    $element->setUnite($unit);
                    $element->setCalories($calories);

                    if ($recipeId > 0) {
                        $recette = $entityManager->getRepository(Recette::class)->findOneBy(['spoonacularId' => $recipeId]);
                        if ($recette instanceof Recette) {
                            $element->setRecette($recette);
                        }
                    }

                    $totalCalories += $calories;
                    $repas->addElementRepas($element);
                }
            }

            if ($totalCalories <= 0) {
                $this->addFlash('warning', 'Les calories doivent être supérieures à 0.');
            } elseif ($hasInputErrors) {
                // Les erreurs sont déjà remontées via flash messages.
            } else {
                $repas->setTotalCalories($totalCalories);
                $violations = $validator->validate($repas);
                if (count($violations) > 0) {
                    $this->addFlash('warning', (string) $violations->get(0)->getMessage());
                } else {
                    $entityManager->persist($repas);
                    $entityManager->flush();

                    return $this->redirectToRoute('repas');
                }
            }
        }

        return $this->render('repas/edit.html.twig', [
            'repas' => $repas,
        ]);
    }

    /**
     * @param array{title?: mixed, image?: mixed, calories?: mixed, servings?: mixed} $recipe
     */
    private function upsertApiRecipe(EntityManagerInterface $entityManager, int $spoonacularId, array $recipe): ?Recette
    {
        if ($spoonacularId <= 0) {
            return null;
        }

        $recette = $entityManager->getRepository(Recette::class)->findOneBy(['spoonacularId' => $spoonacularId]);
        if (!$recette instanceof Recette) {
            $recette = new Recette();
            $recette->setSpoonacularId($spoonacularId);
            $entityManager->persist($recette);
        }

        $title = trim((string) ($recipe['title'] ?? ''));
        $servings = (int) ($recipe['servings'] ?? 1);

        $recette->setTitre($title !== '' ? $title : 'Recette');
        $recette->setImageUrl(isset($recipe['image']) ? (string) $recipe['image'] : null);
        $recette->setCaloriesParPortion(isset($recipe['calories']) ? (int) $recipe['calories'] : null);
        $recette->setServings($servings > 0 ? $servings : 1);
        $recette->setUpdatedAt(new \DateTimeImmutable('now'));

        return $recette;
    }

    #[Route('/repas/{id}/delete', name: 'repas_delete', methods: ['POST'])]
    public function delete(Request $request, Repas $repas, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('REPAS_DELETE', $repas);

        if ($this->isCsrfTokenValid('delete_repas_' . $repas->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($repas);
            $entityManager->flush();
        }

        return $this->redirectToRoute('repas');
    }

    private function parseMealDate(string $dateRaw): ?\DateTimeImmutable
    {
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $dateRaw);
            if ($date instanceof \DateTimeImmutable) {
                return $date;
            }
        }

        return null;
    }
}
