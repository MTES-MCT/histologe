<?php

namespace App\Service\DashboardTabPanel;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class TabBodyLoaderCollection
{
    /**
     * @var iterable|TabBodyLoaderInterface[]
     */
    private iterable $tabBodyLoaders;

    /**
     * @param iterable<TabBodyLoaderInterface> $tabBodyLoaders
     */
    public function __construct(
        #[AutowireIterator('app.tab_body_loader')] iterable $tabBodyLoaders,
    ) {
        $this->tabBodyLoaders = $tabBodyLoaders;
    }

    public function load(TabBody $tabBody): void
    {
        foreach ($this->tabBodyLoaders as $loader) {
            if ($loader->supports($tabBody->getType())) {
                $loader->load($tabBody);
                break;
            }
        }
    }
}
