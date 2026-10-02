/*
 * Liste les ressources créées ou modifiées pendant une période donnée.
 *
 * Paramètres psql attendus :
 *   date_debut : début de la période, inclus
 *   date_fin   : fin de la période, exclue
 */

SELECT type_ressource,
       id,
       name,
       created_at,
       updated_at,
       creator_id,
       createur,
       archived,
       CASE
           WHEN created_at >= :'date_debut'::timestamptz
               AND created_at < :'date_fin'::timestamptz
               THEN 'Création'
           ELSE 'Modification'
           END AS action
FROM (SELECT CASE
                 WHEN rc.type = 'model' THEN 'Modèle'
                 WHEN rc.type = 'metric' THEN 'Métrique'
                 ELSE 'Question'
                 END AS type_ressource,
             rc.id,
             rc.name,
             rc.created_at,
             rc.updated_at,
             rc.creator_id,
             u.email AS createur,
             rc.archived
      FROM report_card rc
               LEFT JOIN core_user u ON u.id = rc.creator_id

      UNION ALL

      SELECT 'Tableau de bord',
             d.id,
             d.name,
             d.created_at,
             d.updated_at,
             d.creator_id,
             u.email,
             d.archived
      FROM report_dashboard d
               LEFT JOIN core_user u ON u.id = d.creator_id) ressources
WHERE (
    created_at >= :'date_debut'::timestamptz
        AND created_at < :'date_fin'::timestamptz
    )
   OR (
    updated_at >= :'date_debut'::timestamptz
        AND updated_at < :'date_fin'::timestamptz
    )
ORDER BY GREATEST(created_at, updated_at) DESC;
