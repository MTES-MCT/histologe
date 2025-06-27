import { Page } from '@playwright/test';

export async function waitForVueAppToLoad(page: Page, timeout = 30000) {
  console.log('🔄 Waiting for Vue app container...');
  
  // Attendre que l'application VueJS soit initialisée
  await page.waitForSelector('#app-signalement-form-container', { timeout });
  console.log('✅ Vue app container found');
  
  // Attendre que le contenu soit chargé (l'application VueJS a fini de se charger)
  await page.waitForFunction(() => {
    const container = document.querySelector('#app-signalement-form-container');
    return container && 
           container.children.length > 0 && 
           !container.textContent?.includes('Initialisation') &&
           !container.textContent?.includes('Erreur lors de l\'initialisation');
  }, { timeout });
  console.log('✅ Vue app content loaded');
  
  // Attendre un peu plus pour s'assurer que tout est bien rendu
  await page.waitForTimeout(2000);
  console.log('✅ Vue app rendering completed');
}

export async function waitForVueAppToBeInteractive(page: Page, timeout = 30000) {
  await waitForVueAppToLoad(page, timeout);
  
  console.log('🔄 Waiting for interactive elements...');
  
  // Attendre que les éléments interactifs soient disponibles
  await page.waitForFunction(() => {
    const buttons = document.querySelectorAll('button');
    const inputs = document.querySelectorAll('input, select, textarea');
    const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
    
    console.log(`Found ${buttons.length} buttons, ${inputs.length} inputs, ${headings.length} headings`);
    
    for (const btn of buttons) {
        const text = btn.textContent;
        const name = btn.getAttribute('name');
        const id = btn.getAttribute('id');
        console.log(`Bouton visible: text='${text}', name='${name}', id='${id}'`);
      }
    return buttons.length > 0 || inputs.length > 0 || headings.length > 0;
  }, { timeout });
  
  console.log('✅ Interactive elements found');
  
  // Attendre un peu plus pour s'assurer que tout est bien rendu
  await page.waitForTimeout(1000);
}

export async function waitForSpecificElement(page: Page, selector: string, timeout = 30000) {
  console.log(`🔄 Waiting for specific element: ${selector}`);
  
  try {
    await page.waitForSelector(selector, { timeout });
    console.log(`✅ Element found: ${selector}`);
    return true;
  } catch (error) {
    console.log(`❌ Element not found: ${selector}`);
    
    // Debug: afficher le contenu de la page
    const content = await page.content();
    console.log('Page content preview:', content.substring(0, 1000));
    
    // Debug: afficher tous les éléments avec des rôles
    const elements = await page.evaluate(() => {
      const allElements = document.querySelectorAll('*');
      const elementsWithRoles: Array<{
        tag: string;
        role: string | null;
        text: string | undefined;
        id: string;
        class: string;
      }> = [];
      
      for (const element of allElements) {
        if (element.getAttribute('role') || element.tagName === 'BUTTON' || element.tagName === 'H1' || element.tagName === 'H2' || element.tagName === 'H3') {
          elementsWithRoles.push({
            tag: element.tagName,
            role: element.getAttribute('role'),
            text: element.textContent?.trim().substring(0, 50),
            id: element.id,
            class: element.className
          });
        }
      }
      
      return elementsWithRoles;
    });
    
    console.log('Elements with roles found:', elements);
    
    throw error;
  }
} 