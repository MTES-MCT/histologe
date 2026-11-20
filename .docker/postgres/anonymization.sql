-- ============================================================
-- Signalement : données du déclarant
-- ============================================================

SECURITY LABEL FOR anon ON COLUMN signalement.nom_declarant
IS 'MASKED WITH FUNCTION anon.fake_last_name()';

SECURITY LABEL FOR anon ON COLUMN signalement.prenom_declarant
IS 'MASKED WITH FUNCTION anon.fake_first_name()';

SECURITY LABEL FOR anon ON COLUMN signalement.tel_declarant
IS 'MASKED WITH VALUE ''0600000000''';

SECURITY LABEL FOR anon ON COLUMN signalement.tel_declarant_secondaire
IS 'MASKED WITH VALUE ''0700000000''';

SECURITY LABEL FOR anon ON COLUMN signalement.mail_declarant
IS 'MASKED WITH FUNCTION anon.fake_email()';

SECURITY LABEL FOR anon ON COLUMN signalement.structure_declarant
IS 'MASKED WITH VALUE ''Structure fictive''';

SECURITY LABEL FOR anon ON COLUMN signalement.matricule_declarant
IS 'MASKED WITH VALUE ''MAT-ANONYME''';

-- ============================================================
-- Signalement : données de l'occupant
-- ============================================================

SECURITY LABEL FOR anon ON COLUMN signalement.nom_occupant
IS 'MASKED WITH FUNCTION anon.fake_last_name()';

SECURITY LABEL FOR anon ON COLUMN signalement.prenom_occupant
IS 'MASKED WITH FUNCTION anon.fake_first_name()';

SECURITY LABEL FOR anon ON COLUMN signalement.tel_occupant
IS 'MASKED WITH VALUE ''0600000000''';

SECURITY LABEL FOR anon ON COLUMN signalement.mail_occupant
IS 'MASKED WITH FUNCTION anon.fake_email()';

-- ============================================================
-- ADDRESS : anonymisation des adresses postales
-- ============================================================

UPDATE address
SET
    ban_id = NULL,
    point = NULL,
    housenumber = '12',
    street = 'Rue Fictive ' || id::text;
