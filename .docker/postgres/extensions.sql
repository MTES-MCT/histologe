-- Extensions PostgreSQL
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS anon;
-- Initialisation des dictionnaires d'anonymisation
SELECT anon.init();
