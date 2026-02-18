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

        $this->denyAccessUnlessGranted(
            'REPAS_VIEW', $repas);

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
        // Endpoint d'API pour la recherche de recettes, utilisé par le formulaire de création de repas. Accepte les paramètres de requête suivants :
        $query = trim($request->query->getString('q', ''));
        // Si la requête de recherche est vide, retourner une réponse JSON avec un tableau de résultats vide pour éviter les appels inutiles à l'API Spoonacular.
        if ($query === '') {
            return $this->json(['results' => []]);
        }

        // Les paramètres de calories min et max sont optionnels,
        // avec des valeurs par défaut raisonnables pour guider les utilisateurs vers des recettes adaptées à un repas typique.
        $min = (int) $request->query->getString('min', '0');
        $max = (int) $request->query->getString('max', '10000');

        // Appeler le service Spoonacular pour rechercher des recettes correspondant à la requête et aux critères de calories.

        try {
            $results = $spoonacularClient->searchRecipes($query, $min, $max);
        } catch (\Throwable) {
            return $this->json([
                'results' => [],
                'message' => 'Le service de recettes est temporairement indisponible. Réessayez dans quelques instants.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
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


        $perMealTarget = (int) round($targetCalories / 3);
        $minCalories = (int) round($perMealTarget * 0.85);
        $maxCalories = (int) round($perMealTarget * 1.15);

        // Récupération des recettes de l'API Spoonacular
        $session = $request->getSession();
        $sessionKey = 'repas_selected_recipes';
        $formErrors = [];
        $fieldErrors = [];
        $addError = static function (array &$errors, array &$fields, string $message, ?string $field = null): void {
            $errors[] = $message;
            if ($field !== null) {
                $fields[$field][] = $message;
            }
        };
        /** @var array<int, array{id: int, title: string, image: string|null, calories: int|null, servings: int|null, qty: float}> $selectedRecipes */
        $selectedRecipes = $session->get($sessionKey, []);

        $searchQuery = $request->query->getString('search', '');

        $selectRecipeId = $request->query->getInt('selectRecipe');
        
        $removeRecipeId = $request->query->getInt('removeRecipe');
        $isFreshPageLoad = !$request->isMethod('POST')
            && $searchQuery === ''
            && $selectRecipeId <= 0
            && $removeRecipeId <= 0;
        if ($isFreshPageLoad) {
            $selectedRecipes = [];
            $session->remove($sessionKey);
        }
        $spoonacularResults = [];
        if ($searchQuery !== '') {
            try {
                $spoonacularResults = $spoonacularClient->searchRecipes($searchQuery, $minCalories, $maxCalories);
            } catch (\Throwable) {
                $addError($formErrors, $fieldErrors, 'Le service de recettes est indisponible pour le moment. Réessayez plus tard.', 'apiSearch');
            }
        }

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

        // Traitement du formulaire de création de repas
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('save_repas', 
                $request->request->getString('_token'))) {
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
                $hasInputErrors = true;
                $addError($formErrors, $fieldErrors, 'La date du repas est obligatoire et doit être valide.', 'dateRepas');
            } else {
                $repas->setDateRepas($dateRepas);
                $mealDateValue = $dateRepas->format('Y-m-d\TH:i');
            }

            $typeRepas = $request->request->getString('typeRepas', $selectedMealType ?? 'dejeuner');
            if (!in_array($typeRepas, $allowedMealTypes, true)) {
                $typeRepas = $selectedMealType ?? 'dejeuner';
                $hasInputErrors = true;
                $addError($formErrors, $fieldErrors, 'Le type de repas est invalide.', 'typeRepas');
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
                $addError($formErrors, $fieldErrors, 'La source du repas est invalide.', 'modeAjout');
            }
            $repas->setSource($source);

            $totalCalories = 0;

            if ($modeAjout === 'manual') {
                $manualName = trim($request->request->getString('manualName', ''));
                $manualCalories = (int) $request->request->getString('manualCalories', '0');
                $manualQty = (float) $request->request->getString('manualQty', '1');

                if ($manualName === '') {
                    $hasInputErrors = true;
                    $addError($formErrors, $fieldErrors, 'Le nom du plat est obligatoire en mode manuel.', 'manualName');
                }
                if ($manualCalories <= 0) {
                    $hasInputErrors = true;
                    $addError($formErrors, $fieldErrors, 'Les calories doivent être supérieures à 0 en mode manuel.', 'manualCalories');
                }
                if ($manualQty <= 0) {
                    $hasInputErrors = true;
                    $addError($formErrors, $fieldErrors, 'La quantité doit être supérieure à 0 en mode manuel.', 'manualQty');
                }

                // En mode manuel, on n'ajoute l'élément que si tous les champs obligatoires sont valides.
                if (!$hasInputErrors) {
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
                if ($selectedRecipes === []) {
                    $hasInputErrors = true;
                    $addError($formErrors, $fieldErrors, 'Sélectionne au moins une recette avant d\'enregistrer.', 'apiSelection');
                }

                // Les quantités peuvent être personnalisées par l'utilisateur, sinon on prend la quantité par défaut de 1 portion.
                $selectedQty = $request->request->all('selectedQty');
                $hasQtyError = false;
                $hasRecipeDataError = false;
                foreach ($selectedRecipes as $recipeId => $recipe) {
                    $qty = isset($selectedQty[$recipeId]) ? (float) $selectedQty[$recipeId] : (float) ($recipe['qty'] ?? 1);
                    if ($qty <= 0) {
                        $hasInputErrors = true;
                        $hasQtyError = true;
                        continue;
                    }
                    $title = (string) ($recipe['title'] ?? 'Recette');
                    $caloriesPerServing = (int) ($recipe['calories'] ?? 0);

                    if ($title === '' || $caloriesPerServing <= 0) {
                        $hasInputErrors = true;
                        $hasRecipeDataError = true;
                        continue;
                    }

                    $element = new ElementRepas();
                    $element->setRepas($repas);
                    $element->setLibelle($title);
                    $element->setQuantite((string) $qty);
                    $element->setUnite('portion');
                    $elementCalories = (int) round($caloriesPerServing * $qty);
                    $element->setCalories($elementCalories);

                    // Si la recette existe déjà en base (par exemple, ajoutée lors d'un repas précédent), on réutilise l'entité existante pour éviter les doublons. Sinon, on crée une nouvelle entité Recette à partir des données de l'API et on la persiste.
                    $recette = $this->upsertApiRecipe($entityManager, (int) $recipeId, $recipe);
                    if ($recette instanceof Recette) {
                        $element->setRecette($recette);
                    }

                    $totalCalories += $elementCalories;
                    $repas->addElementRepas($element);
                }
                if ($hasQtyError) {
                    $addError($formErrors, $fieldErrors, 'La quantité d\'une recette doit être supérieure à 0.', 'apiSelection');
                }
                if ($hasRecipeDataError) {
                    $addError($formErrors, $fieldErrors, 'Une recette sélectionnée est invalide (titre ou calories manquants).', 'apiSelection');
                }
            }

            if ($hasInputErrors) {
                // Les erreurs sont déjà remontées via le tableau formErrors.
            } elseif ($totalCalories <= 0) {
                $addError($formErrors, $fieldErrors, 'Les calories doivent être supérieures à 0.', 'totalCalories');
            } else {
                $repas->setTotalCalories($totalCalories);
                $violations = $validator->validate($repas);
                if (count($violations) > 0) {
                    $addError($formErrors, $fieldErrors, (string) $violations->get(0)->getMessage(), 'global');
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

        $statusCode = ($request->isMethod('POST') && $formErrors !== [])
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

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
            'formErrors' => $formErrors,
            'fieldErrors' => $fieldErrors,
        ], new Response(status: $statusCode));
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
        $formErrors = [];
        $fieldErrors = [];
        $addError = static function (array &$errors, array &$fields, string $message, ?string $field = null): void {
            $errors[] = $message;
            if ($field !== null) {
                $fields[$field][] = $message;
            }
        };

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
                $addError($formErrors, $fieldErrors, 'La date du repas est invalide.', 'dateRepas');
            } else {
                $repas->setDateRepas($dateRepas);
            }

            $typeRepas = $request->request->getString('typeRepas', $repas->getTypeRepas());
            if (!in_array($typeRepas, $allowedMealTypes, true)) {
                $typeRepas = $repas->getTypeRepas() ?? 'dejeuner';
                $hasInputErrors = true;
                $addError($formErrors, $fieldErrors, 'Le type de repas est invalide.', 'typeRepas');
            }
            $repas->setTypeRepas($typeRepas);

            $notes = $request->request->getString('note', '');
            $repas->setNotes($notes !== '' ? $notes : null);

            $source = $request->request->getString('source', $repas->getSource());
            if (!in_array($source, $allowedSources, true)) {
                $source = $repas->getSource() ?? 'manuel';
                $hasInputErrors = true;
                $addError($formErrors, $fieldErrors, 'La source du repas est invalide.', 'source');
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
                        $hasInputErrors = true;
                        $addError($formErrors, $fieldErrors, 'Un élément du repas est invalide (libellé, quantité ou calories).', 'elements');
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
                $addError($formErrors, $fieldErrors, 'Les calories doivent être supérieures à 0.', 'totalCalories');
            } elseif ($hasInputErrors) {
                // Les erreurs sont déjà remontées via le tableau formErrors.
            } else {
                $repas->setTotalCalories($totalCalories);
                $violations = $validator->validate($repas);
                if (count($violations) > 0) {
                    $addError($formErrors, $fieldErrors, (string) $violations->get(0)->getMessage(), 'global');
                } else {
                    $entityManager->persist($repas);
                    $entityManager->flush();

                    return $this->redirectToRoute('repas');
                }
            }
        }

        $statusCode = ($request->isMethod('POST') && $formErrors !== [])
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

        return $this->render('repas/edit.html.twig', [
            'repas' => $repas,
            'formErrors' => $formErrors,
            'fieldErrors' => $fieldErrors,
        ], new Response(status: $statusCode));
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
