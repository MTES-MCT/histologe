# Histologe.beta.gouv.fr

## Installation

### Cloner

    git clone https://github.com/MTES-MCT/histologe

### Initialisation base de données

Si non existante, créer une base de données locale.
Insérer le jeu de données (export sql) existant (voir avec l'équipe).

### Initialisation fichier .env

Créer le fichier env.local à la racine avec les informations suivantes :

    APP_ENV=dev
    DATABASE_URL="mysql://URL_LOCALE"
    MAILER_DSN=sendinblue+api://SENDINBLUE_KEY@default
    ADMIN_EMAIL=YOUR_EMAIL_ADDRESS

### Lancement local

    composer install
    symfony server:start
