# Historique des adresses : filtres dépendants (zone, territoire)

Guide pour modifier les listes de suggestions des filtres de l'écran « Historique des adresses » (Vue.js) qui dépendent d'un autre filtre.

## Deux mécanismes, selon le filtre

| Filtre | Mécanisme | Pourquoi |
|---|---|---|
| **Territoire** | Appel Ajax `GET /bo/settings?context=addresses-history&territoryId=X` → le store est remplacé | Les données changent complètement, le volume est trop grand pour tout charger |
| **Zone** | **Filtrage local côté front**, sans appel Ajax | Les adresses du territoire sont déjà chargées ; le back renvoie pour chacune ses `zoneIds` |

## Filtre par zone (local)

- **Back** : `AddressesHistoryQuery::findAllList($territory)` renvoie, pour chaque adresse : `id`, `address`, `city` et `zoneIds` (ids des zones qui contiennent l'adresse, calculés en une requête SQL par `findZoneIdsByAddress()` avec `ST_Contains(zone.area, address.point)`). Ces données transitent par `SearchFilterOptionDataProvider::getData()` (mis en cache) puis `Settings::$addresses`.
- **Front** :
  - `useAddressesHistoryFilters.handleSettingsResponse()` remplit `store.state.addressesSuggestions` (liste complète, utilisée par la vue carte) **et** `store.state.addressesWithZones` (`{address, city, zoneIds}`).
  - `AddressesHistoryListFilters.vue` calcule `addressesSuggestionsForZone` et `communesForZone` (computed) : sans zone → listes complètes ; avec zone → adresses de la zone et villes distinctes de ces adresses (les EPCI ne sont alors pas proposés).
  - `onZoneChange` vide commune et adresse, incrémente `resetKey` (vide les `AppAutoComplete`) puis notifie ; aucun rechargement des settings.
- **Périmètre** : seule la vue liste (`AddressesHistoryListFilters`) applique la limitation. `AddressesHistoryMapFilters` continue d'utiliser `sharedState.addressesSuggestions` / `sharedState.communes` non filtrés (il n'y a pas de filtre zone sur la carte). Ne pas modifier ces listes du store pour filtrer, faire un computed local dans le composant concerné.

## Flux du territoire (Ajax)

```
AddressesHistoryListFilters.vue.onTerritoryChange → useAddressesHistoryFilters.reloadSettings()
  → api.ts getSettings → SettingsController::index (/bo/settings)
    → SettingsFactory::createInstanceFrom → SearchFilterOptionDataProvider::getData (cache par territoire/contexte)
  ← handleSettingsResponse() remplit le store
```

`AddressesHistory.vue` appelle `initFiltersFromUrl()` puis `reloadSettings()` au montage : une zone présente dans l'URL est donc appliquée localement dès le chargement.

## Ajouter un nouveau filtre qui limite d'autres listes

1. Si les données nécessaires sont déjà chargées (ou peuvent l'être légèrement) : ajouter l'attribut dans le tableau renvoyé par `findAllList()` (et `types.ts` / `handleSettingsResponse`), puis créer un computed local dans `AddressesHistoryListFilters.vue`.
2. Sinon seulement : passer par un paramètre de `/bo/settings` (`SettingsController` avec contrôle d'accès → `SettingsFactory` → `getData()`), et **l'ajouter à `getCacheKey()`**.
3. Dans le handler de changement, vider les filtres dépendants et incrémenter `resetKey`.
4. Penser aux autres chemins qui modifient le filtre : `onFiltersReset` et `onRemoveFilter` (les computed se mettent à jour seuls, mais les filtres dépendants restent à vider si besoin).

## Pièges

- La zone n'a pas de lien direct avec `Address` : l'appartenance est géométrique (`ST_Contains`, avec `zone.territory_id = address.territory_id`).
- Ne pas charger `Zone::area` inutilement (colonne lourde) : `ZoneRepository::findForUserAndTerritory` utilise un `partial`.
- La liste renvoyée par `findAllList` est mise en cache 1h ; l'invalidation passe par les tags `SearchFilterOptionDataProvider::CACHE_TAG` (ex. `ArreteImportController`). Après modification des zones, le cache n'est pas invalidé automatiquement.
- Dans `findAllList`, le bloc « au moins 2 signalements ou 1 arrêté » est actuellement commenté (WIP branche #5948).
