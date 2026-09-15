<?php

namespace App\Repository\Query;

use App\Repository\EmailDeliveryIssueRepository;

class EmailDeliveryIssueExistsQuery
{
    public function __construct(
        private readonly EmailDeliveryIssueRepository $emailDeliveryIssueRepository,
    ) {
    }

    public function getExistsByEmailDql(string $emailField): string
    {
        $qb = $this->emailDeliveryIssueRepository->createQueryBuilder('edi');
        $expr = $qb->expr()->eq('edi.email', $emailField);
        $qb->select('1')->where($expr);

        return $qb->getDQL();
    }
}
