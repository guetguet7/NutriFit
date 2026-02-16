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

const initMealModeSwitch = () => {
    const switchRoot = document.querySelector('[data-meal-mode-switch]');
    if (!switchRoot || switchRoot.dataset.initialized === '1') {
        return;
    }

    const radios = Array.from(switchRoot.querySelectorAll('input[name="modeAjout"]'));
    const panels = Array.from(document.querySelectorAll('[data-meal-mode-panel]'));
    if (radios.length === 0 || panels.length === 0) {
        return;
    }

    const setPanelState = (panel, disabled) => {
        panel.hidden = disabled;
        panel.querySelectorAll('input, select, textarea, button').forEach((element) => {
            element.disabled = disabled;
        });
    };

    const syncPanels = () => {
        const selectedMode = radios.find((radio) => radio.checked)?.value || 'api';
        panels.forEach((panel) => {
            setPanelState(panel, panel.dataset.mealModePanel !== selectedMode);
        });
    };

    radios.forEach((radio) => {
        radio.addEventListener('change', syncPanels);
    });

    syncPanels();
    switchRoot.dataset.initialized = '1';
};

document.addEventListener('DOMContentLoaded', initMealModeSwitch);
document.addEventListener('turbo:load', initMealModeSwitch);
