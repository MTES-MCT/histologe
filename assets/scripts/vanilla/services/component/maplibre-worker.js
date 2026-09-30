// maplibre-gl v6 est optimisé pour Vite
// mais ne peut pas localiser son script worker une fois empaqueté par Webpack
// Donc on copie les fichiers de maplibre-gl dans le build et on indique à maplibre-gl où trouver son worker
// TODO : quand on supprimera back_addresses_history.js, on pourra supprimer ce fichier et déplacer le setWorkerUrl() ailleurs
import { setWorkerUrl } from 'maplibre-gl';

setWorkerUrl('/build/maplibre-gl/maplibre-gl-worker.mjs');
