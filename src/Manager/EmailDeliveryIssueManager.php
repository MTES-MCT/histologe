<?php

namespace App\Manager;

use App\Entity\EmailDeliveryIssue;
use App\Repository\EmailDeliveryIssueRepository;
use App\Utils\TrimHelper;
use Doctrine\Persistence\ManagerRegistry;

class EmailDeliveryIssueManager extends AbstractManager
{
    public function __construct(
        private EmailDeliveryIssueRepository $emailDeliveryIssueRepository,
        protected ManagerRegistry $managerRegistry,
        string $entityName = EmailDeliveryIssue::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function removeEmailDeliveryIssue(string $oldEmail, ?string $newEmail = null): void
    {
        if (empty($oldEmail)) {
            return;
        }
        if ($newEmail && mb_strtolower(TrimHelper::safeTrim($oldEmail)) === mb_strtolower(TrimHelper::safeTrim($newEmail))) {
            return;
        }
        $emailDeliveryIssue = $this->emailDeliveryIssueRepository->findOneBy(['email' => $oldEmail]);
        if ($emailDeliveryIssue) {
            $this->remove($emailDeliveryIssue);
        }
    }
}
