-- =============================================================================
-- Index spatiaux PostGIS (GiST)
-- Optimisation des calculs d'inclusion et de proximité géométrique (ST_Contains, etc.)
-- =============================================================================

CREATE INDEX IF NOT EXISTS idx_zone_area_gist ON zone USING GIST (area);
