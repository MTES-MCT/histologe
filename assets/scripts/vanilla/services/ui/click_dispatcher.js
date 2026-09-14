// Un seul document.addEventListener('click', ...) réel par page.
// Les contrôleurs s'enregistrent ici au lieu de poser leur propre listener sur document.

const routes = []; // { selector, handler(target, event) } - déclenché quand event.target.closest(selector) matche
const rawHandlers = []; // handler(event) - déclenché à chaque clic, pour les patterns "clic en dehors de X"

export function registerClickRoute(selector, handler) {
  routes.push({ selector, handler });
}

export function registerClickHandler(handler) {
  rawHandlers.push(handler);
}

document.addEventListener('click', (event) => {
  for (const { selector, handler } of routes) {
    const target = event.target.closest(selector);
    if (!target) continue;
    try {
      handler(target, event);
    } catch (error) {
      console.error(`[click_dispatcher] route handler error for "${selector}"`, error);
    }
  }

  for (const handler of rawHandlers) {
    try {
      handler(event);
    } catch (error) {
      console.error('[click_dispatcher] raw handler error', error);
    }
  }
});
