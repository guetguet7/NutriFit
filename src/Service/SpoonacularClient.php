<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SpoonacularClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $spoonacularApiKey,
    ) {
    }

    /// Effectue une recherche de recettes en fonction de la requête et des contraintes caloriques, avec une tolérance pour les résultats vides.
    /**
     * @return array<int, array{id: int|null, title: string, image: string|null, calories: int|null, servings: int|null}>
     */
    public function searchRecipes(string $query, int $minCalories, int $maxCalories, int $limit = 8): array
    {
        // Si la clé API n'est pas configurée, on lève une exception explicite pour permettre l'affichage d'un message d'erreur côté interface.
        if ($this->spoonacularApiKey === '') {
            //runtime exception est utilisée ici pour signaler une erreur de configuration critique qui empêche le fonctionnement normal du service, ce qui est approprié dans ce contexte où l'absence de la clé API rend le service inutilisable.
            throw new \RuntimeException('La clé API Spoonacular n\'est pas configurée.');
        }

        //la recherche de recettes est effectuée en deux étapes : d'abord avec les contraintes caloriques, puis sans les contraintes si aucun résultat n'est trouvé, afin d'augmenter les chances d'obtenir des résultats pertinents malgré les limitations de l'API.
        $recipes = $this->fetchRecipes($query, $limit, $minCalories, $maxCalories);

        // la recette de Spoonacular peut parfois ne pas respecter les contraintes caloriques, ce qui peut entraîner des résultats vides même lorsque des recettes pertinentes existent.
        if ($recipes === []) {
            $recipes = $this->fetchRecipes($query, $limit, null, null);
        }

        return $recipes;
    }

    /**
     * @return array<int, array{id: int|null, title: string, image: string|null, calories: int|null, servings: int|null}>
     */
    //la méthode "fetchRecipes" est responsable de l'appel à l'API de Spoonacular pour récupérer les recettes en fonction des critères de recherche, avec une validation minimale des données pour garantir la robustesse du code face aux réponses inattendues de l'API.
    private function fetchRecipes(string $query, int $limit, ?int $minCalories, ?int $maxCalories): array
    {
        $queryParams = [
            'apiKey' => $this->spoonacularApiKey,//la clé API est essentielle pour authentifier les requêtes auprès de l'API de Spoonacular, et elle doit être incluse dans tous les appels pour garantir l'accès aux données.

            'query' => $query,//la recherche de recettes est effectuée en utilisant le point d'entrée "complexSearch" de l'API de Spoonacular, qui permet de filtrer les résultats en fonction de divers critères, y compris les calories.

            'number' => $limit,//le nombre de résultats à retourner est contrôlé par le paramètre "number", qui est défini en fonction de la limite spécifiée dans la méthode "searchRecipes".

            'addRecipeNutrition' => true,//en ajoutant le paramètre "addRecipeNutrition" à la requête, on peut obtenir des informations nutritionnelles détaillées pour chaque recette, ce qui est nécessaire pour calculer les calories totales de la recette.
        ];

        //les contraintes caloriques sont ajoutées à la requête uniquement si elles sont spécifiées, ce qui permet de faire des recherches plus flexibles et d'éviter les erreurs lorsque les valeurs sont nulles.
        if ($minCalories !== null) {
            $queryParams['minCalories'] = $minCalories;
        }
        //le paramètre "maxCalories" est ajouté à la requête uniquement si une valeur est fournie, ce qui permet de faire des recherches plus flexibles et d'éviter les erreurs lorsque la valeur est nulle.
        if ($maxCalories !== null) {
            $queryParams['maxCalories'] = $maxCalories;
        }
        
        //L'API de Spoonacular peut parfois retourner des réponses mal formées ou des structures inattendues,
        //il est donc important d'ajouter des vérifications pour éviter les erreurs de type et garantir que le code reste robuste face à ces situations.
        
        $response = $this->httpClient->request('GET', 'https://api.spoonacular.com/recipes/complexSearch', [
            'query' => $queryParams,
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('Réponse invalide du service Spoonacular.');
        }

        //en utilisant "toArray(false)", on peut éviter les exceptions en cas de réponse mal formée, ce qui permet de gérer les erreurs de manière plus souple et d'assurer que le code continue à fonctionner même lorsque l'API retourne des données inattendues.
        $payload = $response->toArray(false);

        //la validation minimale des données est essentielle pour garantir que le code ne tente pas d'accéder à des clés ou des indices qui n'existent pas, ce qui pourrait entraîner des erreurs de type ou des exceptions.
        if (!isset($payload['results']) || !is_array($payload['results'])) {
            return [];
        }

        $recipes = [];
        //l'API de Spoonacular ne fournit pas les calories au niveau de la recette, mais au niveau de chaque ingrédient.
        //Il faut donc faire la somme des calories de tous les ingrédients pour obtenir les calories totales de la recette.
        foreach ($payload['results'] as $item) {
            $calories = null;
            if (isset($item['nutrition']['nutrients']) && is_array($item['nutrition']['nutrients'])) {
                foreach ($item['nutrition']['nutrients'] as $nutrient) {
                    if (($nutrient['name'] ?? '') === 'Calories') {
                        $calories = isset($nutrient['amount']) ? (int) round((float) $nutrient['amount']) : null;
                        break;
                    }
                }
            }

            //cela inclut une validation minimale pour éviter les erreurs de type et garantir que les champs essentiels sont présents, 
            //tout en restant tolérant aux données manquantes ou mal formées de l'API.
            $recipes[] = [
                'id' => isset($item['id']) ? (int) $item['id'] : null,
                'title' => (string) ($item['title'] ?? 'Recette'),
                'image' => $item['image'] ?? null,
                'calories' => $calories,
                'servings' => isset($item['servings']) ? (int) $item['servings'] : null,
            ];
        }

        return $recipes;
    }

    /**
     * Fetches detailed recipe information (including instructions/summary) for display pages.
     *
     * @return array{instructions: string|null, summary: string|null}
     */
    public function getRecipeDetails(int $recipeId): array
    {
        //Si la clé API n'est pas configurée ou si l'ID de recette est invalide, retourner des valeurs nulles pour éviter les erreurs côté client.
        if ($this->spoonacularApiKey === '' || $recipeId <= 0) {
            return ['instructions' => null, 'summary' => null];
        }

        //L'API de Spoonacular fournit les instructions et le résumé de la recette dans une requête séparée, il faut donc faire un appel dédié pour récupérer ces informations.
        $response = $this->httpClient->request('GET', sprintf('https://api.spoonacular.com/recipes/%d/information', $recipeId), [
            'query' => [
                'apiKey' => $this->spoonacularApiKey,
                'includeNutrition' => false,
            ],
        ]);

        $payload = $response->toArray(false);

        $instructions = isset($payload['instructions']) && is_string($payload['instructions'])
            ? $payload['instructions']
            : null;
        $summary = isset($payload['summary']) && is_string($payload['summary'])
            ? $payload['summary']
            : null;

        return [
            'instructions' => $instructions,
            'summary' => $summary,
        ];
    }
}
