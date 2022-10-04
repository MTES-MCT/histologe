<?php

namespace App\Command;

use App\Entity\Partner;
use App\Manager\PartnerManager;
use App\Repository\PartnerRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-partner-insee',
    description: 'Fix partner insee from string to array',
)]
class FixPartnerInseeCommand extends Command
{
    public const REGEX_DELIMITERS = '/[;, ]/';

    public function __construct(private PartnerManager $partnerManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->partnerManager->getRepository();
        $partners = $partnerRepository->findWithCodeInseeNotNull();

        $count = 0;
        /** @var Partner $partner */
        foreach ($partners as $partner) {
            if (1 === \count($partner->getInsee()) && \strlen($partner->getInsee()[0]) > 5) {
                $inseeList = preg_split(self::REGEX_DELIMITERS, $partner->getInsee()[0]);
                $partner->setInsee($inseeList);
                $this->partnerManager->save($partner, false);
                ++$count;
            }
        }
        $this->partnerManager->flush();

        $io->success(sprintf('%s partners updated', $count));

        return Command::SUCCESS;
    }
}
