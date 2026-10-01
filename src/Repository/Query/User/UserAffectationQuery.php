<?php

namespace App\Repository\Query\User;

use App\Entity\Enum\UserStatus;
use Doctrine\ORM\EntityManagerInterface;

class UserAffectationQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function findInactiveWithNbAffectationPending(): array
    {
        $connection = $this->entityManager->getConnection();

        $sql = 'SELECT u.email, count(*) as nb_signalements, u.created_at, GROUP_CONCAT(a.signalement_id) as signalements
                FROM user u
                LEFT JOIN user_partner up ON up.user_id = u.id
                LEFT JOIN affectation a ON a.partner_id = up.partner_id AND a.statut = 0
                WHERE u.statut LIKE \''.UserStatus::INACTIVE->value.'\' AND DATE(u.created_at) <= (DATE(NOW()) - INTERVAL 10 DAY)
                AND u.roles NOT LIKE "%ROLE_USAGER%"
                GROUP BY u.email, u.created_at
                ORDER BY nb_signalements desc';

        $pendingUsers = $connection->executeQuery($sql)->fetchAllAssociative();

        return array_map(static function ($pendingUser) {
            return [
                'email' => $pendingUser['email'],
                'nb_signalements' => (!empty($pendingUser['signalements'])) ? (int) $pendingUser['nb_signalements'] : 0,
                'created_at' => $pendingUser['created_at'],
            ];
        }, $pendingUsers);
    }
}
