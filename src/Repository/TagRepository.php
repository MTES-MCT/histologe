<?php

namespace App\Repository;

use App\Controller\Back\TagController;
use App\Entity\Tag;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findAllActive(
        Territory|null $territory = null,
    ):mixed {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere('t.isArchive != 1')
            ->orderBy('t.label', 'ASC')
            ->indexBy('t', 't.id');
        if ($territory) {
            $qb->andWhere('t.territory = :territory')
                ->setParameter('territory', $territory);
        }
        return $qb->getQuery()
            ->getResult();
    }

    
    public function findAllActivePaginated(
        Territory|null $territory = null,
        ?string $search = null,
        ?int $page = 1
    ): array {
        $parameters = [];

        // La requête en sql (plutôt que dql) est obligatoire, car on n'a pas de classe pour la liaison tag-signalement
        $sql = 'SELECT COUNT(ts.tag_id) as nb_tags, t.id, t.label, CONCAT(ter.zip, " - ", ter.name) AS territory_label
                FROM tag t
                LEFT JOIN tag_signalement ts ON ts.tag_id = t.id
                LEFT JOIN territory ter ON ter.id = t.territory_id
                WHERE t.is_archive != 1';

        if (!empty($territory)) {
            $parameters['territory_id'] = $territory->getId();
            $sql .= ' AND ter.id = :territory_id';
        }
        if (!empty($search)) {
            $parameters['search'] = '%' . $search . '%';
            $sql .= ' AND t.label LIKE :search';
        }

        $sql .= ' GROUP BY t.id
                ORDER BY t.label ASC';

        $connection = $this->getEntityManager()->getConnection();
        $completeList = $connection->prepare($sql)
            ->executeQuery($parameters)
            ->fetchAllAssociative();
        $total = \count($completeList);

        // On fait deux fois la requêtes pour récupérer d'abord le total et ensuite le filtre par page, à cause de l'utilisation du sql
        $maxResult = TagController::MAX_LIST_PAGINATION;
        $firstResult = ($page - 1) * $maxResult;
        $sql .= ' LIMIT ' .$maxResult. ' OFFSET ' .$firstResult;
        $completeList = $connection->prepare($sql)
            ->executeQuery($parameters)
            ->fetchAllAssociative();

        return [
            'total' => $total,
            'list' => $completeList
        ];
    }
}
