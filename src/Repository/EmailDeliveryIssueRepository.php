<?php

namespace App\Repository;

use App\Entity\EmailDeliveryIssue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailDeliveryIssue>
 */
class EmailDeliveryIssueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailDeliveryIssue::class);
    }

    public function getExistsByEmailDql(string $emailField): string
    {
        $sub = $this->createQueryBuilder('edi')
            ->select('1')
            ->where("edi.email = {$emailField}");

        return $sub->getDQL();
    }
}
