const disableSmoothScroll = () => {
  cy.document().then(document => {
    const node = document.createElement('style');
    node.innerHTML = 'html { scroll-behavior: inherit !important; }';
    document.body.appendChild(node);
  });
};

describe('Simple test for the Signalement interface', () => {
  it('Displays the form for Signalement', () => {
    cy.visit('http://localhost:8080/signalement')
    disableSmoothScroll()
    cy.get('#signalement-step-1')
  })

  it('Works for first tab', () => {
    cy.get('label[for=signalement_isNotOccupant_0]').click()
    cy.get('#signalement-occupant').should('be.visible')
    cy.get('#signalement_nomOccupant').type('Fragione')
    cy.get('#signalement_prenomOccupant').type('Philippe')
    cy.get('#signalement_telOccupant').type('0612345678')
    cy.get('#signalement_mailOccupant').type('akh@gmail.com')
    cy.get('#signalement_adresseOccupant').type("13006 Marseille 5 rue d' Italie")
    // --
    // Normalement rempli automatiquement en cliquant, mais le click est aléatoire à cause de la recherche et du DOM
    cy.get('#signalement_cpOccupant').type("13006")
    cy.get('#signalement_villeOccupant').type("Marseille")
    cy.wait(500)
    // --
    cy.get('#signalement-adresse-suggestion .fr-adresse-suggestion').first().click()
    cy.get('#signalement_nomProprio').type('IAM')
    cy.get('#signalement-step-1 .fr-grid-row button.fr-btn.checkterr').should('be.visible')
  })

  it('Works for second tab', () => {
    cy.get('#signalement-step-1 .fr-grid-row button.fr-btn.checkterr').click()
    cy.wait(500)
    cy.get('#signalement-step-2').should('be.visible')
    cy.window().scrollTo('top')

    cy.get('#signalement-step-2 > .fr-accordions-group > li').first().click()
    cy.get('#signalement-step-2 > .fr-accordions-group > li ul.fr-toggle__list div.fr-pb-0').first().click()
    cy.get('#signalement-step-2 > .fr-accordions-group > li ul.fr-toggle__list div.fr-collapse--expanded').first('div.fr-radio-group').first().click()

    cy.get('#signalement-step-2 .fr-grid-row button.fr-fi-arrow-right-line').should('be.visible')
  })

  it('Works for third tab', () => {
    cy.get('#signalement-step-2 .fr-grid-row button.fr-fi-arrow-right-line').click()
    cy.wait(500)
    cy.get('#signalement-step-3-panel').should('be.visible')
    cy.window().scrollTo('top')

    cy.get('#signalement_details').type("Je pense pas à demain, parce que demain c'est loin.")
    
    cy.get('#signalement-step-3-panel .fr-grid-row button.fr-fi-arrow-right-line').should('be.visible')
  })

  it('Works for fourth tab', () => {
    cy.get('#signalement-step-3-panel .fr-grid-row button.fr-fi-arrow-right-line').click()
    cy.wait(500)
    cy.get('#signalement-step-4-panel').should('be.visible')
    cy.window().scrollTo('top')

    cy.get('#signalement_nbAdultes').select("2")
    cy.get('#signalement_nbEnfantsP6').select("3")
    cy.get('#signalement_natureLogement').select("appartement")
    
    cy.get('#signalement-step-4-panel .fr-grid-row button.fr-fi-arrow-right-line').should('be.visible')
  })

  it('Works for last tab', () => {
    cy.get('#signalement-step-4-panel .fr-grid-row button.fr-fi-arrow-right-line').click()
    cy.wait(500)
    cy.get('#signalement-step-last-panel').should('be.visible')
    cy.window().scrollTo('top')

    cy.get('label[for=signalement-accept-visite]').click()
    cy.get('label[for=signalement-accept-cgu]').click()

    cy.get('#signalement-step-last-panel #form_finish_submit').should('be.visible')
  })

  it('Submits the form', () => {
    cy.get('#signalement-step-last-panel #form_finish_submit').click()
    cy.wait(2000)
    cy.get('#signalement-success').should('be.visible')
  })

})

before(() => {
  cy.clearCookie('PHPSESSID')
  Cypress.Cookies.defaults({
    preserve: "PHPSESSID"
  })
});

describe('Test a real user login', () => {
  it('Displays the form for login', () => {
    cy.visit('http://localhost:8080/connexion')
    disableSmoothScroll()
    cy.get('form[name=login-form]').should('be.visible')
  })

  it('Submits the login form', () => {
    cy.get('#login-email').type('admin-01@histologe.fr')
    cy.get('#login-password').type('histologe')
    cy.get('form[name=login-form] button').click()
    cy.get('#fr-sidemenu-wrapper').should('be.visible')
  })
})

describe('Simple test for back-office statistics', () => {
  it('Displays the page of the statistics', () => {
    cy.get('#fr-sidemenu-wrapper').contains('Données chiffrées').click()
    cy.get('#fr-sidemenu-pilotage').contains('Statistiques').click()
    disableSmoothScroll()
    cy.get('#app-stats').should('be.visible')
    cy.get('#filter-territoires').should('be.visible')
  })

  it('Change select Statut and Type, then reset', () => {
    cy.get('#filter-statut').select('new')
    cy.get('#filter-type').select('public')
    cy.get('#histo-stats-filters').contains('Tout réinitialiser').click()
    cy.get('#filter-statut').should('have.value', 'all')
    cy.get('#filter-type').should('have.value', 'all')
  })
})

describe('Test front statistics page', () => {
  it('Displays the page of statistiques', () => {
    cy.visit('http://localhost:8080/statistiques')
    disableSmoothScroll()
    cy.get('#app-front-stats').should('be.visible')
  })

  it('Changes current territory', () => {
    cy.get('#filter-territoires').select('1')
  })
})

describe('Test submit partner with user', () => {
  it('Displays partner page', () => {
    cy.visit('http://localhost:8080/bo/partner/new')
    disableSmoothScroll()
    cy.get('#partner_nom').type('Partner')
    cy.get('#partner_isCommune').select('0')
    cy.get('#partner_email').type('partner@yopmail.com')
    cy.get('#partner_add_user').click()
    cy.get('#partner_users_nom_new0').type('Doe')
    cy.get('#partner_users_prenom_new0').type('John')
    cy.get('#partner_users_email_new0').type('john.doe@yopmail.com')
    cy.get('#partner_users_roles_new0').select('ROLE_USER_PARTNER')
    cy.get('#partner_users_is_mailing_active_new0').select('1')
    cy.get('#partner_territory').select('1')
    cy.get('#submit_btn_partner').click()
    cy.get('.fr-alert.fr-alert--success.fr-alert--sm').should('be.visible')
  })
})


