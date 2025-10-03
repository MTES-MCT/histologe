<?php

namespace App\Repository\Query\SignalementList;

use App\Dto\SignalementAffectationListView;
use App\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;

readonly class ListPaginatorQuery
{
    public function __construct(private QueryBuilderFactory $queryBuilderFactory)
    {
    }

    /**
     * @param array<string,mixed> $options
     */
    public function paginate(User $user, array $options): Paginator
    {
        $maxResult = $options['maxItemsPerPage'] ?? SignalementAffectationListView::MAX_LIST_PAGINATION;
        $page = \array_key_exists('page', $options) ? (int) $options['page'] : 1;
        $firstResult = (max($page, 1) - 1) * $maxResult;

        $qb = $this->queryBuilderFactory->create($user, $options);
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult)->getQuery();

        return new Paginator($qb, true);
    }
}
