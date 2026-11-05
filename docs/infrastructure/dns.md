# Gestion DNS — Signal Logement

> **Statut :** documentation d'exploitation.  
> **Périmètre :** zone `signal-logement.beta.gouv.fr` et routage vers Scalingo / OGO Security.  
> **Important :** vérifier les enregistrements actifs dans OVH avant toute intervention. 
>
## 1. Objectif et responsabilités

La zone DNS `signal-logement.beta.gouv.fr` est administrée dans **OVH**. Elle contient les enregistrements du domaine principal, des sous-domaines applicatifs et des services de messagerie.

- **OVH** : zone DNS autoritaire, historique et restauration.
- **OGO Security** : WAF placé devant les domaines basculés.
- **Scalingo** : hébergement des applications.
- **Vaultwarden** : conservation des exports horodatés de la zone DNS, conformément à la procédure de réponse à incident.

La configuration de domaines personnalisés sur Scalingo reste nécessaire même lorsque les requêtes transitent par OGO.

## 2. Inventaire des domaines applicatifs

| Domaine public | Type / destination DNS connue | Origine configurée dans OGO | État / remarque |
| --- | --- | --- | --- |
| `signal-logement.beta.gouv.fr` | `A` vers des IP d'infrastructure Scalingo | `https://histologe-sites-faciles.osc-fr1.scalingo.io` | **Bascule OGO non résolue** : racine de zone, CNAME interdit |
| `bo.signal-logement.beta.gouv.fr` | `CNAME` vers `p1-ogo-euw2-6.shield.ogosecurity.com.` | `https://histologe.osc-fr1.scalingo.io` | Bascule OGO réalisée selon les informations d'exploitation |
| `service-secours.signal-logement.beta.gouv.fr` | `CNAME` vers `p1-ogo-euw2-6.shield.ogosecurity.com.` | `https://histologe.osc-fr1.scalingo.io` | Bascule OGO réalisée selon les informations d'exploitation |
| `demo.signal-logement.beta.gouv.fr` | `CNAME` vers `histologe-demo.osc-fr1.scalingo.io.` | — | Hors périmètre OGO décrit ici |
| `service-secours.demo.signal-logement.beta.gouv.fr` | `CNAME` vers `histologe-demo.osc-fr1.scalingo.io.` | — | Hors périmètre OGO décrit ici |
| `documentation.signal-logement.beta.gouv.fr` | `CNAME` vers `2da0050bbd-hosting.gitbook.io.` | — | Documentation externe |

Les domaines `bo` et `service-secours` utilisent **la même origine Scalingo** ; ne pas mettre en place une redirection canonique globale qui renverrait involontairement l'un vers l'autre.

### Enregistrements A historiques de la racine

https://doc.scalingo.com/platform/networking/public/ingress

La zone enregistrée comporte les quatre adresses suivantes :

```dns
@ IN A 5.104.101.30
@ IN A 109.232.233.130
@ IN A 109.232.236.90
@ IN A 148.253.75.120
```

La zone comporte également des enregistrements `SOA`, `NS`, `MX`, `TXT` (notamment SPF et vérifications de domaine), ainsi que des entrées liées à la messagerie. Les préserver lors de toute opération.

## 3. Architecture des flux

### Requêtes entrantes sur un sous-domaine protégé

```text
Client / partenaire
       |
       v
DNS OVH : CNAME -> p1-ogo-euw2-6.shield.ogosecurity.com
       |
       v
OGO Security (filtrage WAF)
       |
       v
Scalingo -> application Signal Logement
```

Les connexions reçues par Scalingo depuis OGO ont pour IP réseau source une **IP de sortie OGO**. Si une politique de filtrage IP est appliquée à l'origine, utiliser les plages officielles communiquées par OGO et les maintenir à jour : <https://api.ogosecurity.com/public-ip-list>.


### Appels sortants depuis Signal Logement

```text
Application Signal Logement (Scalingo)
       |
       v
Réseau de sortie Scalingo
       |
       v
API partenaire (Esabora, DGFiP, etc.)
```

La mise en place du WAF **entrant** ne modifie normalement pas le chemin de sortie des appels HTTP effectués par l'application. 
## 4. Limitation DNS à la racine

`signal-logement.beta.gouv.fr` est le domaine racine de notre zone DNS chez OVH. 
Il possède obligatoirement des enregistrements SOA et NS, ce qui empêche la configuration d’un CNAME classique, même après suppression des enregistrements A.


### Options de résolution

**Option privilégiée si supportée par OGO :** obtenir d'OGO (fabrique numérique) des **IP d'entrée fixes et officiellement utilisables dans des enregistrements `A`/`AAAA`**. 

**Décision en attente :** validation par OGO (fabrique numérique) de la méthode officiellement supportée pour configurer le domaine racine chez OVH. Ne pas supprimer les `A` de production en attendant.

## 6. Procédure de modification DNS applicatifs

### Avant intervention

- [ ] Identifier le domaine, l'origine applicative.
- [ ] Exporter la zone DNS OVH et conserver une sauvegarde horodatée dans **Vaultwarden**, coffre `DNS`.
- [ ] Noter la valeur précédente, le TTL et la procédure de retour arrière.
- [ ] Vérifier la présence du domaine dans Scalingo.
- [ ] Confirmer les paramètres OGO : domaine, destination.
- [ ] Informer les utilisateurs concernés de l'intervention, de sa date et des éventuelles perturbations du service.

### Pendant l'intervention

- [ ] Modifier uniquement les enregistrements nécessaires dans OVH.
- [ ] Ne pas modifier `NS`, `MX`, `TXT`, `SPF`, ect... sans justification explicite.
- [ ] Contrôler la résolution via la ligne de commande `dig`.
- [ ] Vérifier HTTPS, les redirections et les journaux OGO / Scalingo.

### Après intervention

- [ ] Tester les domaines concernés depuis l'extérieur.
- [ ] Tester les formulaires, l'authentification, les API et webhooks.
- [ ] Vérifier que les appels sortants vers les partenaires restent opérationnels https://bo.signal-logement.beta.gouv.fr/bo/connexions-si/.
- [ ] Contrôler la détection de la bascule et les éventuels blocages et faux positifs sur le WAF.
- [ ] Exporter la zone mise à jour dans Vaultwarden et consigner la modification.

## 7. Commandes de diagnostic

```bash
# Vérifier les serveurs de noms
 dig +short NS signal-logement.beta.gouv.fr

# Racine : résolution A 
 dig +short A signal-logement.beta.gouv.fr

# Sous-domaines : CNAME
 dig +short CNAME bo.signal-logement.beta.gouv.fr
 dig +short CNAME service-secours.signal-logement.beta.gouv.fr

# Cible OGO : adresses résolues à l'instant T (NON garanties fixes)
 dig +short A p1-ogo-euw2-6.shield.ogosecurity.com
 dig +short AAAA p1-ogo-euw2-6.shield.ogosecurity.com

# Vérifier les réponses HTTP
 curl -I https://signal-logement.beta.gouv.fr/
 curl -I https://bo.signal-logement.beta.gouv.fr/
 curl -I https://service-secours.signal-logement.beta.gouv.fr/

```

Les réponses de `dig` reflètent l'état de résolution au moment du test.

## 8. Documentation

- [Architecture technique](../ARCHITECTURE.md).
- [Procédure de compromission DNS](../incident-response/procedure-dns-compromission.md) — restauration OVH, vérifications et sauvegardes Vaultwarden.
- [OGO — Bascule DNS](https://aide.ogosecurity.com/help/bascule-dns).


## 9. Points à confirmer / maintenir

- [ ] Confirmer avec OGO (fabrique numérique) la prise en charge d'IP d'entrée fixes sur le domaine principale.
- [ ] Mettre à jour [ARCHITECTURE.md](../ARCHITECTURE.md) pour refléter les évolutions de l'infrastructure réseau.
