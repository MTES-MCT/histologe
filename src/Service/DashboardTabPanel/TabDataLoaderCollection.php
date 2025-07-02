<?php

namespace App\Service\DashboardTabPanel;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class TabDataLoaderCollection
{
    /**
     * @var iterable|TabDataLoaderInterface[]
     */
    private iterable $tabDataLoaders;

    /**
     * @param iterable<TabDataLoaderInterface> $tabDataLoaders
     */
    public function __construct(
        #[AutowireIterator('app.tab_data_loader')] iterable $tabDataLoaders,
    ) {
        $this->tabDataLoaders = $tabDataLoaders;
    }

    public function load(TabData $tabData): void
    {
        foreach ($this->tabDataLoaders as $loader) {
            if ($loader->supports($tabData->getType())) {
                $loader->load($tabData);
                break;
            }
        }
    }
}
