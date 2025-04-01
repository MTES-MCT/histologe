# Politiques et procédures de sécurité

## Objectif du document
Ce document décrit les procédures de sécurité et les politiques générales pour le projet Signal Logement.
L’objectif de ce document est de définir les actions manuelles et les outils à mettre en place pour assurer une veille proactive et continue sur les vulnérabilités des composants du Système d’Information (SI). Cela inclut la mise à jour des correctifs de sécurité publiés, l’abonnement à des flux d’information de sécurité et la documentation des processus de mise à jour.

## Veille sur les Vulnérabilités
### Outils de Surveillance et de Reporting
#### Dependabot
[Dependabot](https://github.com/MTES-MCT/histologe/security/dependabot) est utilisé pour surveiller les dépendances de nos projets et notifier l’équipe technique des mises à jour disponibles, y compris les correctifs de sécurité. Il est intégré avec GitHub et déclenche des pull requests automatiques pour les mises à jour.

#### SonarCloud
[SonarCloud](https://github.com/MTES-MCT/histologe/security/code-scanning) est utilisé pour analyser le code source de nos projets afin de détecter les vulnérabilités, les bugs, et les mauvaises pratiques de codage. Il fournit des rapports détaillés et des recommandations pour améliorer la qualité et la sécurité du code.   

### Abonnement à des Flux d'Informations sur la Sécurité
1. **CERT-FR** :
   Abonnez-vous aux alertes et bulletins de sécurité du CERT-FR. [CERT-FR](https://www.cert.ssi.gouv.fr/actualite/).

2. **NVD (National Vulnerability Database)** :
   Suivez les mises à jour et les alertes sur les vulnérabilités publiées par la NVD. [NVD RSS Feeds](https://nvd.nist.gov/).

3. **CVE (Common Vulnerabilities and Exposures)** :
   Abonnez-vous aux notifications CVE pour suivre les nouvelles vulnérabilités identifiées. [CVE Announcements](https://github.com/CVEProject/cvelistV5).

4. **Security Mailing Lists** :
   Inscrivez-vous aux listes de diffusion de sécurité des principaux fournisseurs de logiciels et matériels que vous utilisez.
     - [Ubuntu Security Announcements](https://lists.ubuntu.com/mailman/listinfo/ubuntu-security-announce).
     - [Red Hat Security Announcements](https://www.redhat.com/mailman/listinfo).

6. **GitHub Security Advisories** :
   Suivez les bulletins de sécurité sur GitHub pour les projets que vous utilisez. [GitHub Security Advisories](https://github.com/advisories).

### Suivi des Bulletins de Sécurité des Fabricants et Éditeurs
1. **Oracle Security Alerts** :
   Suivez les mises à jour de sécurité pour les produits Oracle, y compris MySQL. [Oracle Security Alerts](https://www.oracle.com/security-alerts/).

2. **PHP Group** :
   Surveillez les annonces de sécurité et les versions mises à jour du langage PHP. PHP Security](https://www.php.net/security).

3. **Symfony** :
   Consultez les annonces et mises à jour de sécurité pour le framework Symfony. [Symfony Security Advisories](https://symfony.com/blog/category/security-advisories).

4. **Nginx** :
   Suivez les mises à jour de sécurité pour Nginx. [Nginx Releases](https://nginx.org/en/CHANGES).

## Documentation

Le registre des équipements et applicatifs peut se trouver sur le [cloud de l'équipe](https://docs.google.com/spreadsheets/d/1KzdbRt-o58UL4Qtdzn5akOc9XmykcoEks5yClO0zrJc/edit?gid=0#gid=0). Ce registre contient la liste de tous les équipements et applicatifs avec la version utilisée et les versions actuelles, ainsi qu'une procédure de mise à jour.


## Signaler une faille
L’équipe de Signal Logement prend très au sérieux la sécurité de l’application. Signalez toute faille de sécurité en envoyant un mail à l’équipe via le [formulaire de contact du site](https://signal-logement.beta.gouv.fr/contact).

L’équipe accusera réception de votre mail dans les 72 heures. Après la réponse initiale à votre rapport, elle vous tiendra informé de la progression vers une correction et une annonce complète, et pourra vous demander des informations ou des conseils supplémentaires.

Si vous faites partie de la communauté, vous pouvez en informer l'équipe technique sur le canal **startup - Signal Logement (ex histologe)** de Mattermost
Créer un ticket sans le publier (pour ne pas le rendre visible) dans la partie https://github.com/MTES-MCT/histologe/security/advisories

### Politique de divulgation et de correction

Lorsque l’équipe reçoit un rapport sur une faille de sécurité, elle procède aux étapes suivantes :

- Confirmer le problème et déterminer les versions affectées.
- Vérifier le code pour trouver tout problème similaire potentiel.
- Communiquer par mattermost aux différentes instances connues qu'une faille est en cours de résolution
- Préparer les correctifs, les merger sur la branche production et les déployer
- Communiquer par mattermost aux différentes instances connues que le correctif est disponible sur la branche principale

### Commentaires sur cette politique
Si vous avez des suggestions sur la façon dont ce processus pourrait être amélioré, veuillez soumettre une demande de téléchargement.
