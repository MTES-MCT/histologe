-- =============================================================================
-- Fonctions de compatibilité MySQL -> PostgreSQL
-- =============================================================================

-- 1. Fonctions JSON (JSON_LENGTH, JSON_CONTAINS)
CREATE OR REPLACE FUNCTION json_length(doc json) RETURNS integer AS $$
BEGIN
    IF doc IS NULL THEN
        RETURN NULL;
    END IF;
    CASE json_typeof(doc)
        WHEN 'array' THEN
            RETURN json_array_length(doc);
        WHEN 'object' THEN
            RETURN (SELECT count(*)::integer FROM json_object_keys(doc));
        WHEN 'null' THEN
            RETURN NULL;
        ELSE
            RETURN 1;
    END CASE;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_length(doc jsonb) RETURNS integer AS $$
BEGIN
    IF doc IS NULL THEN
        RETURN NULL;
    END IF;
    CASE jsonb_typeof(doc)
        WHEN 'array' THEN
            RETURN jsonb_array_length(doc);
        WHEN 'object' THEN
            RETURN (SELECT count(*)::integer FROM jsonb_object_keys(doc));
        WHEN 'null' THEN
            RETURN NULL;
        ELSE
            RETURN 1;
    END CASE;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_length(doc text) RETURNS integer AS $$
BEGIN
    IF doc IS NULL THEN
        RETURN NULL;
    END IF;
    RETURN json_length(doc::json);
EXCEPTION WHEN OTHERS THEN
    RETURN NULL;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_contains(target json, candidate json) RETURNS integer AS $$
BEGIN
    IF target IS NULL OR candidate IS NULL THEN
        RETURN NULL;
    END IF;
    RETURN CASE WHEN target::jsonb @> candidate::jsonb THEN 1 ELSE 0 END;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_contains(target jsonb, candidate jsonb) RETURNS integer AS $$
BEGIN
    IF target IS NULL OR candidate IS NULL THEN
        RETURN NULL;
    END IF;
    RETURN CASE WHEN target @> candidate THEN 1 ELSE 0 END;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_contains(target text, candidate text) RETURNS integer AS $$
BEGIN
    IF target IS NULL OR candidate IS NULL THEN
        RETURN NULL;
    END IF;
    RETURN CASE WHEN target::jsonb @> candidate::jsonb THEN 1 ELSE 0 END;
EXCEPTION WHEN OTHERS THEN
    RETURN 0;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

-- 2. Fonctions Date / Chaîne (MONTH, YEAR, DATEDIFF, IFNULL)
CREATE OR REPLACE FUNCTION month(d timestamp with time zone) RETURNS integer AS $$
    SELECT EXTRACT(MONTH FROM d)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION month(d timestamp without time zone) RETURNS integer AS $$
    SELECT EXTRACT(MONTH FROM d)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION month(d date) RETURNS integer AS $$
    SELECT EXTRACT(MONTH FROM d)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION year(d timestamp with time zone) RETURNS integer AS $$
    SELECT EXTRACT(YEAR FROM d)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION year(d timestamp without time zone) RETURNS integer AS $$
    SELECT EXTRACT(YEAR FROM d)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION year(d date) RETURNS integer AS $$
    SELECT EXTRACT(YEAR FROM d)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION datediff(d1 timestamp with time zone, d2 timestamp with time zone) RETURNS integer AS $$
    SELECT (d1::date - d2::date)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION datediff(d1 timestamp without time zone, d2 timestamp without time zone) RETURNS integer AS $$
    SELECT (d1::date - d2::date)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION datediff(d1 date, d2 date) RETURNS integer AS $$
    SELECT (d1 - d2)::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION ifnull(a anycompatible, b anycompatible) RETURNS anycompatible AS $$
    SELECT COALESCE(a, b);
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

-- =============================================================================
-- Compatibilité de comparaison Boolean <-> Integer (MySQL / PostgreSQL)
-- Permet l'évaluation transparente de requêtes telles que `is_active = 1` ou `is_active <> 0`
-- =============================================================================

-- 1. Fonctions de comparaison Boolean <-> Integer
CREATE OR REPLACE FUNCTION bool_int_eq(boolean, integer) RETURNS boolean AS $$
    SELECT $1 = ($2 <> 0);
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION int_bool_eq(integer, boolean) RETURNS boolean AS $$
    SELECT ($1 <> 0) = $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION bool_int_neq(boolean, integer) RETURNS boolean AS $$
    SELECT $1 <> ($2 <> 0);
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION int_bool_neq(integer, boolean) RETURNS boolean AS $$
    SELECT ($1 <> 0) <> $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

-- Opérateurs Boolean <-> Integer
CREATE OPERATOR = (
    LEFTARG = boolean,
    RIGHTARG = integer,
    FUNCTION = bool_int_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR = (
    LEFTARG = integer,
    RIGHTARG = boolean,
    FUNCTION = int_bool_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR <> (
    LEFTARG = boolean,
    RIGHTARG = integer,
    FUNCTION = bool_int_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

CREATE OPERATOR <> (
    LEFTARG = integer,
    RIGHTARG = boolean,
    FUNCTION = int_bool_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

-- 2. Fonctions de comparaison Boolean <-> Smallint
CREATE OR REPLACE FUNCTION bool_smallint_eq(boolean, smallint) RETURNS boolean AS $$
    SELECT $1 = ($2 <> 0);
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION smallint_bool_eq(smallint, boolean) RETURNS boolean AS $$
    SELECT ($1 <> 0) = $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION bool_smallint_neq(boolean, smallint) RETURNS boolean AS $$
    SELECT $1 <> ($2 <> 0);
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION smallint_bool_neq(smallint, boolean) RETURNS boolean AS $$
    SELECT ($1 <> 0) <> $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

-- Opérateurs Boolean <-> Smallint
CREATE OPERATOR = (
    LEFTARG = boolean,
    RIGHTARG = smallint,
    FUNCTION = bool_smallint_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR = (
    LEFTARG = smallint,
    RIGHTARG = boolean,
    FUNCTION = smallint_bool_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR <> (
    LEFTARG = boolean,
    RIGHTARG = smallint,
    FUNCTION = bool_smallint_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

CREATE OPERATOR <> (
    LEFTARG = smallint,
    RIGHTARG = boolean,
    FUNCTION = smallint_bool_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

-- 3. Fonctions de comparaison Boolean <-> Bigint
CREATE OR REPLACE FUNCTION bool_bigint_eq(boolean, bigint) RETURNS boolean AS $$
    SELECT $1 = ($2 <> 0);
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION bigint_bool_eq(bigint, boolean) RETURNS boolean AS $$
    SELECT ($1 <> 0) = $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION bool_bigint_neq(boolean, bigint) RETURNS boolean AS $$
    SELECT $1 <> ($2 <> 0);
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION bigint_bool_neq(bigint, boolean) RETURNS boolean AS $$
    SELECT ($1 <> 0) <> $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

-- Opérateurs Boolean <-> Bigint
CREATE OPERATOR = (
    LEFTARG = boolean,
    RIGHTARG = bigint,
    FUNCTION = bool_bigint_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR = (
    LEFTARG = bigint,
    RIGHTARG = boolean,
    FUNCTION = bigint_bool_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR <> (
    LEFTARG = boolean,
    RIGHTARG = bigint,
    FUNCTION = bool_bigint_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

CREATE OPERATOR <> (
    LEFTARG = bigint,
    RIGHTARG = boolean,
    FUNCTION = bigint_bool_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

-- =============================================================================
-- Compatibilité de comparaison JSON / JSONB <-> TEXT (MySQL / PostgreSQL)
-- Permet l'évaluation de requêtes telles que `p.insee != '[]'` ou `p.insee LIKE :term`
-- =============================================================================

-- 1. JSON <-> TEXT
CREATE OR REPLACE FUNCTION json_text_eq(json, text) RETURNS boolean AS $$
    SELECT $1::text = $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_text_neq(json, text) RETURNS boolean AS $$
    SELECT $1::text <> $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION text_json_eq(text, json) RETURNS boolean AS $$
    SELECT $1 = $2::text;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION text_json_neq(text, json) RETURNS boolean AS $$
    SELECT $1 <> $2::text;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OPERATOR = (
    LEFTARG = json,
    RIGHTARG = text,
    FUNCTION = json_text_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR = (
    LEFTARG = text,
    RIGHTARG = json,
    FUNCTION = text_json_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR <> (
    LEFTARG = json,
    RIGHTARG = text,
    FUNCTION = json_text_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

CREATE OPERATOR <> (
    LEFTARG = text,
    RIGHTARG = json,
    FUNCTION = text_json_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

-- LIKE (~~) et NOT LIKE (!~~) pour JSON <-> TEXT
CREATE OR REPLACE FUNCTION json_text_like(json, text) RETURNS boolean AS $$
    SELECT $1::text LIKE $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_text_not_like(json, text) RETURNS boolean AS $$
    SELECT $1::text NOT LIKE $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OPERATOR ~~ (
    LEFTARG = json,
    RIGHTARG = text,
    FUNCTION = json_text_like,
    NEGATOR = !~~
);

CREATE OPERATOR !~~ (
    LEFTARG = json,
    RIGHTARG = text,
    FUNCTION = json_text_not_like,
    NEGATOR = ~~
);

-- ILIKE (~~*) et NOT ILIKE (!~~*) pour JSON <-> TEXT
CREATE OR REPLACE FUNCTION json_text_ilike(json, text) RETURNS boolean AS $$
    SELECT $1::text ILIKE $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_text_not_ilike(json, text) RETURNS boolean AS $$
    SELECT $1::text NOT ILIKE $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OPERATOR ~~* (
    LEFTARG = json,
    RIGHTARG = text,
    FUNCTION = json_text_ilike,
    NEGATOR = !~~*
);

CREATE OPERATOR !~~* (
    LEFTARG = json,
    RIGHTARG = text,
    FUNCTION = json_text_not_ilike,
    NEGATOR = ~~*
);

-- 2. JSONB <-> TEXT
CREATE OR REPLACE FUNCTION jsonb_text_eq(jsonb, text) RETURNS boolean AS $$
    SELECT $1::text = $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION jsonb_text_neq(jsonb, text) RETURNS boolean AS $$
    SELECT $1::text <> $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION text_jsonb_eq(text, jsonb) RETURNS boolean AS $$
    SELECT $1 = $2::text;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION text_jsonb_neq(text, jsonb) RETURNS boolean AS $$
    SELECT $1 <> $2::text;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OPERATOR = (
    LEFTARG = jsonb,
    RIGHTARG = text,
    FUNCTION = jsonb_text_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR = (
    LEFTARG = text,
    RIGHTARG = jsonb,
    FUNCTION = text_jsonb_eq,
    COMMUTATOR = =,
    NEGATOR = <>
);

CREATE OPERATOR <> (
    LEFTARG = jsonb,
    RIGHTARG = text,
    FUNCTION = jsonb_text_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

CREATE OPERATOR <> (
    LEFTARG = text,
    RIGHTARG = jsonb,
    FUNCTION = text_jsonb_neq,
    COMMUTATOR = <>,
    NEGATOR = =
);

CREATE OR REPLACE FUNCTION jsonb_text_like(jsonb, text) RETURNS boolean AS $$
    SELECT $1::text LIKE $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION jsonb_text_not_like(jsonb, text) RETURNS boolean AS $$
    SELECT $1::text NOT LIKE $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OPERATOR ~~ (
    LEFTARG = jsonb,
    RIGHTARG = text,
    FUNCTION = jsonb_text_like,
    NEGATOR = !~~
);

CREATE OPERATOR !~~ (
    LEFTARG = jsonb,
    RIGHTARG = text,
    FUNCTION = jsonb_text_not_like,
    NEGATOR = ~~
);

CREATE OR REPLACE FUNCTION jsonb_text_ilike(jsonb, text) RETURNS boolean AS $$
    SELECT $1::text ILIKE $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION jsonb_text_not_ilike(jsonb, text) RETURNS boolean AS $$
    SELECT $1::text NOT ILIKE $2;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OPERATOR ~~* (
    LEFTARG = jsonb,
    RIGHTARG = text,
    FUNCTION = jsonb_text_ilike,
    NEGATOR = !~~*
);

CREATE OPERATOR !~~* (
    LEFTARG = jsonb,
    RIGHTARG = text,
    FUNCTION = jsonb_text_not_ilike,
    NEGATOR = ~~*
);

-- =============================================================================
-- Fonctions de compatibilité spatiale (PostGIS / MySQL)
-- Support de ST_Contains(geometry, point), ST_Within, ST_Intersects, etc.
-- =============================================================================

CREATE OR REPLACE FUNCTION point(x text, y text) RETURNS point AS $$
BEGIN
    RETURN point(x::double precision, y::double precision);
EXCEPTION WHEN OTHERS THEN
    RETURN NULL;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION st_contains(geom geometry, pt point) RETURNS boolean AS $$
BEGIN
    IF geom IS NULL OR pt IS NULL THEN
        RETURN FALSE;
    END IF;
    RETURN ST_Contains(geom, pt::geometry);
EXCEPTION WHEN OTHERS THEN
    RETURN FALSE;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION st_contains(pt point, geom geometry) RETURNS boolean AS $$
BEGIN
    IF geom IS NULL OR pt IS NULL THEN
        RETURN FALSE;
    END IF;
    RETURN ST_Contains(pt::geometry, geom);
EXCEPTION WHEN OTHERS THEN
    RETURN FALSE;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION st_within(geom geometry, pt point) RETURNS boolean AS $$
BEGIN
    IF geom IS NULL OR pt IS NULL THEN
        RETURN FALSE;
    END IF;
    RETURN ST_Within(geom, pt::geometry);
EXCEPTION WHEN OTHERS THEN
    RETURN FALSE;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION st_within(pt point, geom geometry) RETURNS boolean AS $$
BEGIN
    IF geom IS NULL OR pt IS NULL THEN
        RETURN FALSE;
    END IF;
    RETURN ST_Within(pt::geometry, geom);
EXCEPTION WHEN OTHERS THEN
    RETURN FALSE;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION st_intersects(geom geometry, pt point) RETURNS boolean AS $$
BEGIN
    IF geom IS NULL OR pt IS NULL THEN
        RETURN FALSE;
    END IF;
    RETURN ST_Intersects(geom, pt::geometry);
EXCEPTION WHEN OTHERS THEN
    RETURN FALSE;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION st_intersects(pt point, geom geometry) RETURNS boolean AS $$
BEGIN
    IF geom IS NULL OR pt IS NULL THEN
        RETURN FALSE;
    END IF;
    RETURN ST_Intersects(pt::geometry, geom);
EXCEPTION WHEN OTHERS THEN
    RETURN FALSE;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

-- =============================================================================
-- Fonctions de compatibilité MySQL (SUBSTRING_INDEX, CAST / types numériques)
-- =============================================================================

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'unsigned') THEN
        CREATE DOMAIN unsigned AS integer;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'signed') THEN
        CREATE DOMAIN signed AS integer;
    END IF;
END;
$$;

CREATE OR REPLACE FUNCTION substring_index(str text, delim text, count integer)
RETURNS text AS $$
DECLARE
    parts text[];
    n integer;
BEGIN
    IF str IS NULL OR delim IS NULL OR count IS NULL THEN
        RETURN NULL;
    END IF;
    IF count = 0 OR delim = '' THEN
        RETURN '';
    END IF;
    parts := string_to_array(str, delim);
    n := coalesce(array_length(parts, 1), 0);
    IF n = 0 THEN
        RETURN str;
    END IF;
    IF count > 0 THEN
        RETURN array_to_string(parts[1:least(count, n)], delim);
    ELSE
        RETURN array_to_string(parts[greatest(n + count + 1, 1):n], delim);
    END IF;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION mysql_cast_int(val text) RETURNS integer AS $$
DECLARE
    matched text;
BEGIN
    IF val IS NULL THEN RETURN NULL; END IF;
    matched := substring(val from '^[0-9]+');
    IF matched IS NOT NULL AND matched != '' THEN
        RETURN matched::integer;
    END IF;
    RETURN 0;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION mysql_cast_int(val integer) RETURNS integer AS $$
    SELECT val;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION mysql_cast_int(val bigint) RETURNS integer AS $$
    SELECT val::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION mysql_cast_int(val smallint) RETURNS integer AS $$
    SELECT val::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION mysql_cast_int(val double precision) RETURNS integer AS $$
    SELECT val::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION mysql_cast_int(val numeric) RETURNS integer AS $$
    SELECT val::integer;
$$ LANGUAGE SQL IMMUTABLE PARALLEL SAFE;

-- =============================================================================
-- Fonction FIND_IN_SET (MySQL compatibility)
-- =============================================================================

CREATE OR REPLACE FUNCTION find_in_set(str text, strlist text)
RETURNS integer AS $$
DECLARE
    parts text[];
    idx integer;
BEGIN
    IF str IS NULL OR strlist IS NULL THEN
        RETURN 0;
    END IF;
    IF str = '' AND strlist = '' THEN
        RETURN 1;
    END IF;
    IF position(',' in str) > 0 THEN
        RETURN 0;
    END IF;
    parts := string_to_array(strlist, ',');
    IF parts IS NULL THEN
        RETURN 0;
    END IF;
    FOR idx IN 1..coalesce(array_length(parts, 1), 0) LOOP
        IF parts[idx] = str THEN
            RETURN idx;
        END IF;
    END LOOP;
    RETURN 0;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

-- =============================================================================
-- Fonctions JSON MySQL (JSON_EXTRACT, JSON_CONTAINS_PATH, JSON path parser)
-- =============================================================================

CREATE OR REPLACE FUNCTION mysql_json_path_to_array(p text) RETURNS text[] AS $$
DECLARE
    cleaned text;
    parts text[];
BEGIN
    IF p IS NULL THEN RETURN NULL; END IF;
    IF p = '$' THEN RETURN ARRAY[]::text[]; END IF;
    IF starts_with(p, '$.') THEN
        cleaned := substring(p from 3);
    ELSIF starts_with(p, '$') THEN
        cleaned := substring(p from 2);
    ELSE
        cleaned := p;
    END IF;
    IF cleaned = '' THEN RETURN ARRAY[]::text[]; END IF;
    cleaned := regexp_replace(cleaned, '\[([0-9]+)\]', '.\1', 'g');
    cleaned := replace(cleaned, '"', '');
    parts := string_to_array(cleaned, '.');
    RETURN parts;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_extract(doc jsonb, path text) RETURNS text AS $$
DECLARE
    path_arr text[];
BEGIN
    IF doc IS NULL OR path IS NULL THEN RETURN NULL; END IF;
    path_arr := mysql_json_path_to_array(path);
    IF path_arr IS NULL THEN RETURN NULL; END IF;
    RETURN doc #>> path_arr;
EXCEPTION WHEN OTHERS THEN
    RETURN NULL;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_extract(doc jsonb, VARIADIC paths text[]) RETURNS text AS $$
DECLARE
    p text;
    res text[];
    single_val text;
BEGIN
    IF doc IS NULL OR paths IS NULL OR coalesce(array_length(paths, 1), 0) = 0 THEN RETURN NULL; END IF;
    IF array_length(paths, 1) = 1 THEN
        RETURN json_extract(doc, paths[1]);
    END IF;
    FOREACH p IN ARRAY paths LOOP
        single_val := json_extract(doc, p);
        res := array_append(res, single_val);
    END LOOP;
    RETURN to_json(res)::text;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_extract(doc json, path text) RETURNS text AS $$
BEGIN
    IF doc IS NULL OR path IS NULL THEN RETURN NULL; END IF;
    RETURN json_extract(doc::jsonb, path);
EXCEPTION WHEN OTHERS THEN
    RETURN NULL;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_extract(doc json, VARIADIC paths text[]) RETURNS text AS $$
BEGIN
    IF doc IS NULL OR paths IS NULL THEN RETURN NULL; END IF;
    RETURN json_extract(doc::jsonb, VARIADIC paths);
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_extract(doc text, path text) RETURNS text AS $$
BEGIN
    IF doc IS NULL OR path IS NULL OR doc = '' THEN RETURN NULL; END IF;
    RETURN json_extract(doc::jsonb, path);
EXCEPTION WHEN OTHERS THEN
    RETURN NULL;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_extract(doc text, VARIADIC paths text[]) RETURNS text AS $$
BEGIN
    IF doc IS NULL OR paths IS NULL OR doc = '' THEN RETURN NULL; END IF;
    RETURN json_extract(doc::jsonb, VARIADIC paths);
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_contains_path(doc jsonb, mode text, VARIADIC paths text[])
RETURNS integer AS $$
DECLARE
    p text;
    path_arr text[];
    has_match boolean;
    all_match boolean := true;
    one_match boolean := false;
BEGIN
    IF doc IS NULL OR mode IS NULL OR paths IS NULL OR coalesce(array_length(paths, 1), 0) = 0 THEN
        RETURN NULL;
    END IF;
    FOREACH p IN ARRAY paths LOOP
        path_arr := mysql_json_path_to_array(p);
        has_match := (path_arr IS NOT NULL AND (doc #> path_arr) IS NOT NULL);
        IF has_match THEN
            one_match := true;
        ELSE
            all_match := false;
        END IF;
    END LOOP;
    IF lower(mode) = 'one' THEN
        RETURN CASE WHEN one_match THEN 1 ELSE 0 END;
    ELSE
        RETURN CASE WHEN all_match THEN 1 ELSE 0 END;
    END IF;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_contains_path(doc json, mode text, VARIADIC paths text[])
RETURNS integer AS $$
BEGIN
    IF doc IS NULL OR mode IS NULL OR paths IS NULL THEN RETURN NULL; END IF;
    RETURN json_contains_path(doc::jsonb, mode, VARIADIC paths);
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE FUNCTION json_contains_path(doc text, mode text, VARIADIC paths text[])
RETURNS integer AS $$
BEGIN
    IF doc IS NULL OR mode IS NULL OR paths IS NULL OR doc = '' THEN RETURN NULL; END IF;
    RETURN json_contains_path(doc::jsonb, mode, VARIADIC paths);
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

-- =============================================================================
-- Fonction / Aggrégat GROUP_CONCAT (MySQL compatibility)
-- =============================================================================

CREATE OR REPLACE FUNCTION group_concat_step(a text, b text) RETURNS text AS $$
BEGIN
    IF a IS NULL THEN RETURN b; END IF;
    IF b IS NULL THEN RETURN a; END IF;
    RETURN a || ',' || b;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE AGGREGATE group_concat(text) (
    SFUNC = group_concat_step,
    STYPE = text
);

CREATE OR REPLACE FUNCTION group_concat_sep_step(a text, b text, sep text) RETURNS text AS $$
BEGIN
    IF a IS NULL THEN RETURN b; END IF;
    IF b IS NULL THEN RETURN a; END IF;
    RETURN a || sep || b;
END;
$$ LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE;

CREATE OR REPLACE AGGREGATE group_concat(text, text) (
    SFUNC = group_concat_sep_step,
    STYPE = text
);
