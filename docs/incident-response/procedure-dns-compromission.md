# 🛠️ Procédure – Restauration et sécurisation d’une zone DNS après compromission

## 🧩 Contexte de l’incident

Un compte administrateur disposant de droits complets sur la gestion DNS a été compromis suite à une session ouverte non verrouillée dans un espace de coworking.  
Un tiers non autorisé a profité de l’absence temporaire de l’administrateur pour accéder à l’interface DNS et effectuer des actions critiques.

🎯 **Objectif :** restaurer rapidement la zone DNS et limiter l’impact sur les utilisateurs.

---

## 🚨 Réactions immédiates

1. **Avertir les personnes concernées**
    - [ ] Informer l’équipe produit et les responsables techniques.

2. **Isoler le compte compromis**
    - [ ] [scalingo] Vérifier que le domaine est toujours attaché au container de production  
      *(Container de production → Paramètres → Domaines)*
    - [ ] [scalingo] Arrêter temporairement le container de production.
    - [ ] [ovh] Révoquer immédiatement les identifiants du compte administrateur OVH.
    - [ ] [ovh] Désactiver les clés API : <https://www.ovh.com/manager/#/iam/api-keys/onboarding>
    - [ ] [ovh] Désactiver les clés SSH : <https://www.ovh.com/manager/#/billing/autorenew/ssh>

---

## 🗃️ Vérification des sauvegardes

- [ ] [ovh] Vérifier l’historique des modifications de la zone DNS et télécharger une version **antérieure à l’incident**  
  👉 <https://www.ovh.com/manager/#/web/zone/signal-logement.beta.gouv.fr/zone-history>
- [ ] [vaultwarden] Récupérer la version de backup sur <https://vaultwarden.incubateur.net> et la **comparer avec l’historique OVH**.

---

## 🔁 Étapes de restauration

1. **Restauration de la zone DNS depuis l’interface OVH**
    - [ ] Si la version **avant incident** est identique à la **version backup**, restaurer directement depuis l’interface OVH via le bouton **« Restaurer »**.
    - [ ] Si les versions diffèrent, restaurer la **version backup manuellement** via le mode **textuel** (`Modifier en mode textuel`).

2. **Redémarrer le container de production**

3. **Vérifier la propagation des modifications**
    - [ ] Utiliser des outils en ligne pour suivre la propagation :
        - <https://dnspropagation.net/>
        - <https://www.whatsmydns.net/>

4. **Exporter la zone corrigée**
    - [ ] Exporter la version corrigée dans Vaultwarden :
        1. Ouvrir le coffre `DNS`
        2. Cloner la précédente sauvegarde
        3. Renommer le fichier : `YYYY-MM-DD-signal-logement.beta.gouv.fr_dns_data.txt`
        4. Enregistrer

---

## ✅ Vérifications post-restauration

- [ ] Vérifier la résolution DNS des domaines principaux
- [ ] Tester les environnements applicatifs (connexion, lecture/écriture)
- [ ] Vérifier que les utilisateurs peuvent utiliser la plateforme

---

## 📣 Communication

- [ ] Informer les équipes internes (tech, produit, support)
- [ ] Préparer une communication si les utilisateurs finaux ont été impactés

---

## 🧠 Analyse post-incident

- [ ] Documenter l’incident dans un **Postmortem**  
  *(template : [template-postmortem.md](template-postmortem.md))*
- [ ] Identifier les causes racines et proposer des mesures préventives (authentification forte, gestion des accès, politique de verrouillage des sessions, etc.)

---

## 🗒️ Note importante – Gestion des sauvegardes DNS

> Toute **modification** apportée à une zone DNS (création, suppression ou modification d’un enregistrement) doit être **systématiquement sauvegardée sur Vaultwarden** dans le coffre dédié, sous la forme d’un fichier `.txt` horodaté.
>
> Cette mesure permet de restaurer rapidement la configuration précédente en cas d’erreur ou d’incident de sécurité.
