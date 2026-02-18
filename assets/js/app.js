import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './charts.js';
import './recipe_search.js';

console.log('Bonjour et bienvenue dans votre application NutriFit ! ');

const initNavToggle = () => {
    const nav = document.querySelector('.site-nav');
    if (!nav || nav.dataset.navToggleInitialized === '1') {
        return;
    }

    const toggleInput = nav.querySelector('.site-nav__toggle-input');
    const toggleButton = nav.querySelector('[data-nav-toggle]');
    const menu = nav.querySelector('#menu');
    if (!toggleInput || !toggleButton || !menu) {
        return;
    }

    const syncState = () => {
        const expanded = toggleInput.checked;
        toggleButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        toggleButton.setAttribute('aria-label', expanded ? 'Fermer le menu principal' : 'Ouvrir le menu principal');
    };

    const closeMenu = () => {
        toggleInput.checked = false;
        syncState();
    };

    toggleInput.checked = false;
    syncState();

    toggleButton.addEventListener('click', () => {
        toggleInput.checked = !toggleInput.checked;
        syncState();
    });

    menu.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('a')) {
            closeMenu();
        }
    });

    nav.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggleInput.checked) {
            event.preventDefault();
            closeMenu();
            toggleButton.focus();
        }
    });

    nav.dataset.navToggleInitialized = '1';
};

const syncMealModePanels = () => {
    const switchRoot = document.querySelector('[data-meal-mode-switch]');
    if (!switchRoot) {
        return;
    }

    const radios = Array.from(switchRoot.querySelectorAll('input[name="modeAjout"]'));
    const panels = Array.from(document.querySelectorAll('[data-meal-mode-panel]'));
    if (radios.length === 0 || panels.length === 0) {
        return;
    }

    const selectedMode = radios.find((radio) => radio.checked)?.value || 'api';
    panels.forEach((panel) => {
        const disabled = panel.dataset.mealModePanel !== selectedMode;
        panel.hidden = disabled;
        panel.querySelectorAll('input, select, textarea, button').forEach((element) => {
            element.disabled = disabled;
        });
    });
};

const handleMealModeChange = (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
        return;
    }
    if (target.name !== 'modeAjout') {
        return;
    }
    if (!target.closest('[data-meal-mode-switch]')) {
        return;
    }

    syncMealModePanels();
};

document.addEventListener('change', handleMealModeChange);
document.addEventListener('DOMContentLoaded', syncMealModePanels);
document.addEventListener('turbo:load', syncMealModePanels);
document.addEventListener('DOMContentLoaded', initNavToggle);
document.addEventListener('turbo:load', initNavToggle);
