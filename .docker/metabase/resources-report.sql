/*
 * Rapport de volumétrie des ressources Metabase.
 *
 * Cette requête comptabilise les collections, questions, modèles,
 * métriques et tableaux de bord présents dans la base applicative Metabase.
 * Elle distingue les ressources actives des ressources archivées.
 *
 * Elle peut être exécutée sur les instances source et cible afin de comparer
 * leur contenu avant et après une migration.
 */

SELECT ressource, total, actives, archivees
FROM (SELECT 1             AS ordre,
             'Collections' AS ressource,
             COUNT(*)      AS total,
             COUNT(*)         FILTER (WHERE archived = FALSE) AS actives, COUNT(*) FILTER (WHERE archived = TRUE) AS archivees
      FROM collection

      UNION ALL

      SELECT 2,
             'Questions',
             COUNT(*),
             COUNT(*) FILTER (WHERE archived = FALSE), COUNT(*) FILTER (WHERE archived = TRUE)
      FROM report_card
      WHERE COALESCE(type, 'question') NOT IN ('model', 'metric')

      UNION ALL

      SELECT 3,
             'Modèles',
             COUNT(*),
             COUNT(*) FILTER (WHERE archived = FALSE), COUNT(*) FILTER (WHERE archived = TRUE)
      FROM report_card
      WHERE type = 'model'

      UNION ALL

      SELECT 4,
             'Métriques',
             COUNT(*),
             COUNT(*) FILTER (WHERE archived = FALSE), COUNT(*) FILTER (WHERE archived = TRUE)
      FROM report_card
      WHERE type = 'metric'

      UNION ALL

      SELECT 5,
             'Tableaux de bord',
             COUNT(*),
             COUNT(*) FILTER (WHERE archived = FALSE), COUNT(*) FILTER (WHERE archived = TRUE)
      FROM report_dashboard) rapport
ORDER BY ordre;
