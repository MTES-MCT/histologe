<?php

namespace App\Command\Cron;

use App\Command\Cron\Handler\ClearEntitiesHandler;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:clear-entities',
    description: 'Clear older records from entities',
)]
class ClearEntitiesCommand extends AbstractCronCommand
{
    /**
     * @param iterable<EntityCleanerRepositoryInterface> $entityCleanerRepositories
     */
    public function __construct(
        readonly private ParameterBagInterface $parameterBag,
        readonly private ClearEntitiesHandler $clearEntitiesHandler,
        #[AutowireIterator('app.entity_cleaner')] private readonly iterable $entityCleanerRepositories,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var ServiceEntityRepository<object>|EntityCleanerRepositoryInterface $entityCleanerRepository */
        foreach ($this->entityCleanerRepositories as $entityCleanerRepository) {
            $entity = explode('\\', $entityCleanerRepository->getClassName());
            $entityName = end($entity);
            $countDeletedSuccess = $this->clearEntitiesHandler->handle(
                fn () => $entityCleanerRepository->cleanOlderThan(),
                $entityName,
                sprintf('Suppression des enregistrements de la table %s', $entityName)
            );

            $io->success($countDeletedSuccess.sprintf(' %s(s) deleted !', $entityName));
        }

        return Command::SUCCESS;
    }
}
