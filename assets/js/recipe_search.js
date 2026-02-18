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

    // Construire et retourner le HTML de la carte de recette
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

// Initialiser la fonctionnalité de recherche de recettes
const initRecipeSearch = () => {
    const searchBlocks = document.querySelectorAll('[data-recipe-search]');

    // Initialiser chaque bloc de recherche de recett. 
    searchBlocks.forEach((block) => {
        if (block.dataset.recipeSearchInitialized === '1') {
            return;
        }
        // Trouver les éléments d'entrée, de soumission, de résultats et de statut associés au bloc de recherche
        const input = block.querySelector('[data-recipe-input]');
        // Trouver le bouton de soumission associé au bloc de recherche
        const submit = block.querySelector('[data-recipe-submit]');
        // Trouver les éléments de résultats et de statut associés au bloc de recherche
        const results = block.parentElement?.querySelector('[data-recipe-results]');
        // Trouver l'élément de statut associé au bloc de recherche pour les annonces d'accessibilité
        const status = block.parentElement?.querySelector('[data-recipe-status]');
        const feedback = block.parentElement?.querySelector('[data-recipe-feedback]');
        
        // Vérifier que tous les éléments nécessaires sont présents avant de continuer
        if (!input || !submit || !results || !status) {
            return;
        }

        // Récupérer les configurations depuis les attributs de données
        const apiUrl = block.dataset.apiUrl || '';
        const minCalories = block.dataset.minCalories || '0';
        const maxCalories = block.dataset.maxCalories || '10000';
        const selectBase = block.dataset.selectBase || '';
        const mealTypeSelect = document.querySelector('#meal-type');
        const announce = (message) => {
            status.textContent = message;
        };
        const setFeedback = (message) => {
            if (!feedback) {
                return;
            }
            feedback.textContent = message;
            feedback.hidden = message === '';
        };

        // Fonction pour exécuter la recherche de recettes 
        const runSearch = async () => {
            const query = input.value.trim();
            if (!query) {
                results.innerHTML = '<p class="empty-state">Saisissez un mot-cle pour lancer la recherche.</p>';
                results.setAttribute('aria-busy', 'false');
                announce('Saisissez un mot-clé pour lancer la recherche.');
                setFeedback('');
                return;
            }
            // Construire l'URL de l'API avec les paramètres de requête
            const url = new URL(apiUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('min', minCalories);
            url.searchParams.set('max', maxCalories);

            // Afficher un message de chargement et indiquer que la recherche est en cours
            results.setAttribute('aria-busy', 'true');
            results.innerHTML = '<p class="empty-state">Chargement...</p>';
            announce('Recherche en cours...');
            setFeedback('');

            // Utiliser AbortController pour gérer les délais d'attente de la requête
            const controller = new AbortController();
            const timeoutId = window.setTimeout(() => controller.abort(), 10000);

            try {
                // Effectuer la requête de recherche
                const response = await fetch(url.toString(), {
                    headers: {
                        // Indiquer que nous attendons une réponse au format JSON
                        Accept: 'application/json',
                    },
                    // Passer le signal d'abandon pour permettre l'annulation de la requête en cas de délai d'attente
                    signal: controller.signal,
                });

                // Vérifier le type de contenu de la réponse pour s'assurer qu'il est au format JSON avant de tenter de le parser
                const contentType = response.headers.get('content-type') || '';
                // Tenter de parser la réponse en JSON si le type de contenu est approprié, sinon utiliser un objet vide
                const payload = contentType.includes('application/json') ? await response.json() : {};

                // Vérifier les erreurs de la réponse de recherche
                if (!response.ok) {
                    const apiMessage = typeof payload.message === 'string' && payload.message !== ''
                        ? payload.message
                        : 'Le service de recettes est temporairement indisponible.';
                    throw new Error(apiMessage);
                }
                const items = Array.isArray(payload.results) ? payload.results : [];

                // Gérer le cas où aucun résultat n'est trouvé
                if (items.length === 0) {
                    results.innerHTML = '<p class="empty-state">Aucun resultat.</p>';
                    results.setAttribute('aria-busy', 'false');
                    announce('Aucun résultat trouvé.');
                    setFeedback('');
                    return;
                }

                // Construire et afficher les cartes de recettes
                results.innerHTML = items
                    .map((item) => {
                        const currentMealType = 
                        ['petit_dej', 'dejeuner', 'diner', 'collation'].includes(mealTypeSelect?.value)
                            ? mealTypeSelect.value
                            : 'dejeuner';
                        const selectUrl = item.id
                            ? `${selectBase}?search=${encodeURIComponent(query)}
                                &selectRecipe=${encodeURIComponent(item.id)}
                                &type=${encodeURIComponent(currentMealType)}`
                            : '#';
                        return buildCard(item, selectUrl);
                    })

                    .join('');
                results.setAttribute('aria-busy', 'false');
                announce(`${items.length} recettes trouvées.`);
                setFeedback('');
            } catch (error) {
                const errorMessage = error instanceof Error && error.name === 'AbortError'
                    ? 'Le service de recettes ne répond pas. Réessayez dans quelques instants.'
                    : (error instanceof Error && error.message !== ''
                        ? error.message
                        : 'Le service de recettes est temporairement indisponible.');
                results.setAttribute('aria-busy', 'false');
                announce(errorMessage);
                setFeedback(errorMessage);
            } finally {
                window.clearTimeout(timeoutId);
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

// Initialiser la fonctionnalité d'estimation du total des calories du repas
const initMealTotalEstimator = () => {
    const totalTarget = document.querySelector('[data-meal-total-calories]');
    if (!totalTarget || totalTarget.dataset.mealTotalInitialized === '1') {
        return;
    }

    // Trouver toutes les cartes de recettes sélectionnées et les champs de quantité associés
    const recipeCards = Array.from(document.querySelectorAll('[data-selected-recipe]'));
    const qtyInputs = Array.from(document.querySelectorAll('[data-recipe-qty-input]'));

    // Fonction pour recalculer et mettre à jour le total des calories du repas
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

    // Ajouter des écouteurs d'événements aux champs de quantité pour recalculer le total lorsque les quantités changent
    qtyInputs.forEach((input) => input.addEventListener('input', refreshTotal));
    totalTarget.dataset.mealTotalInitialized = '1';
    refreshTotal();
};

// Initialiser les fonctionnalités de recherche de recettes et d'estimation du total des calories du repas lorsque le document est prêt ou lorsqu'une nouvelle page est chargée avec Turbo
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
