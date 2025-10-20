<?php

namespace App\Command;

use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:partner-cleanup-email-issue',
    description: 'Supprime la relation des partenaires avec email_delivery_issue si joignable.'
)]
class PartnerCleanupEmailIssueCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PartnerRepository $partnerRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $partners = $this->partnerRepository->findPartnerWithEmailDeliveryIssue();
        $count = 0;

        foreach ($partners as $partner) {
            if (!$partner->hasEmailIssue()) {
                $emailDeliveryIssue = $partner->getEmailDeliveryIssue();
                $partner->setEmailDeliveryIssue(null);
                $this->em->persist($partner);
                $output->writeln(sprintf(
                    'Relation supprimée pour le partenaire #%d (%s)',
                    $partner->getId(),
                    $partner->getNom().' - '.$emailDeliveryIssue->getEmail()
                ));
                ++$count;
            }
        }

        $this->em->flush();

        $output->writeln(sprintf('<info>%d partenaires nettoyés.</info>', $count));

        return Command::SUCCESS;
    }
}
