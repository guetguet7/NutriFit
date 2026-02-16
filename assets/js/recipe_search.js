// Fonction pour échapper les caractères HTML
const escapeHtml = (value) =>
    String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    // Construire une carte de recette HTML
const buildCard = (item, selectUrl) => {
    // Utiliser des valeurs par défaut pour éviter les erreurs d'affichage
    const title = escapeHtml(item.title || 'Recette');
    // Vérifier que les champs numériques sont valides avant de les afficher
    const image = item.image ? `<img src="${escapeHtml(item.image)}" alt="${title}">` : '';
    // Utiliser des valeurs par défaut pour les calories et les portions
    const calories = Number.isFinite(item.calories) ? item.calories : 0;
    // Utiliser une valeur par défaut de 1 pour les portions si la valeur n'est pas valide
    const servings = Number.isFinite(item.servings) ? item.servings : 1;
    // Vérifier si l'ID de la recette est présent pour activer le bouton de sélection
    const canSelect = item.id ? `<a class="btn btn--ghost" href="${selectUrl}">Selectionner</a>` : '<button class="btn btn--ghost" type="button" disabled>Selectionner</button>';

    return `
      <div class="recipe-card">
        <div class="recipe-card__header">
          <div class="recipe-thumb">${image}</div>
          <div class="recipe-card__body">
            <p class="recipe-card__title">${title}</p>
            <small class="recipe-card__meta">${calories} kcal • ${servings} servings</small>
          </div>
        </div>
        <div class="recipe-card__actions">${canSelect}</div>
      </div>
    `;
};

const initRecipeSearch = () => {
    const searchBlocks = document.querySelectorAll('[data-recipe-search]');

    // Initialiser chaque bloc de recherche de recette
    searchBlocks.forEach((block) => {
        if (block.dataset.recipeSearchInitialized === '1') {
            return;
        }

        const input = block.querySelector('[data-recipe-input]');
        const submit = block.querySelector('[data-recipe-submit]');
        const results = block.parentElement?.querySelector('[data-recipe-results]');
        
        if (!input || !submit || !results) {
            return;
        }

        // Récupérer les configurations depuis les attributs de données
        const apiUrl = block.dataset.apiUrl || '';
        const minCalories = block.dataset.minCalories || '0';
        const maxCalories = block.dataset.maxCalories || '10000';
        const selectBase = block.dataset.selectBase || '';
        const mealTypeSelect = document.querySelector('#meal-type');

        const runSearch = async () => {
            const query = input.value.trim();
            if (!query) {
                results.innerHTML = '<p class="empty-state">Saisissez un mot-cle pour lancer la recherche.</p>';
                return;
            }
            // Construire l'URL de l'API avec les paramètres de requête
            const url = new URL(apiUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('min', minCalories);
            url.searchParams.set('max', maxCalories);

            results.innerHTML = '<p class="empty-state">Chargement...</p>';

            try {
                // Effectuer la requête de recherche
                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                // Vérifier les erreurs de la réponse de recherche
                if (!response.ok) {
                    throw new Error(`Requête API échouée avec le statut ${response.status}`);
                }
                const payload = await response.json();
                const items = Array.isArray(payload.results) ? payload.results : [];

                // Gérer le cas où aucun résultat n'est trouvé
                if (items.length === 0) {
                    results.innerHTML = '<p class="empty-state">Aucun resultat.</p>';
                    return;
                }

                // Construire et afficher les cartes de recettes
                results.innerHTML = items
                    .map((item) => {
                        const currentMealType = ['petit_dej', 'dejeuner', 'diner', 'collation'].includes(mealTypeSelect?.value)
                            ? mealTypeSelect.value
                            : 'dejeuner';
                        const selectUrl = item.id
                            ? `${selectBase}?search=${encodeURIComponent(query)}&selectRecipe=${encodeURIComponent(item.id)}&type=${encodeURIComponent(currentMealType)}`
                            : '#';
                        return buildCard(item, selectUrl);
                    })
                    .join('');
            } catch (error) {
                results.innerHTML = '<p class="empty-state">Erreur lors de la recherche.</p>';
            }
        };

        submit.addEventListener('click', runSearch);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                runSearch();
            }
        });

        block.dataset.recipeSearchInitialized = '1';
    });
};

const initMealTotalEstimator = () => {
    const totalTarget = document.querySelector('[data-meal-total-calories]');
    if (!totalTarget || totalTarget.dataset.mealTotalInitialized === '1') {
        return;
    }

    const recipeCards = Array.from(document.querySelectorAll('[data-selected-recipe]'));
    const qtyInputs = Array.from(document.querySelectorAll('[data-recipe-qty-input]'));

    const refreshTotal = () => {
        const total = recipeCards.reduce((sum, card) => {
            const caloriesPerServing = Number.parseFloat(card.dataset.caloriesPerServing || '0');
            const input = card.querySelector('[data-recipe-qty-input]');
            const qty = Number.parseFloat(input?.value ?? '1');
            const safeCalories = Number.isFinite(caloriesPerServing) && caloriesPerServing > 0 ? caloriesPerServing : 0;
            const safeQty = Number.isFinite(qty) && qty > 0 ? qty : 1;

            return sum + Math.round(safeCalories * safeQty);
        }, 0);

        totalTarget.textContent = String(total);
    };

    qtyInputs.forEach((input) => input.addEventListener('input', refreshTotal));
    totalTarget.dataset.mealTotalInitialized = '1';
    refreshTotal();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initRecipeSearch();
        initMealTotalEstimator();
    });
} else {
    initRecipeSearch();
    initMealTotalEstimator();
}
document.addEventListener('turbo:load', initRecipeSearch);
document.addEventListener('turbo:load', initMealTotalEstimator);
