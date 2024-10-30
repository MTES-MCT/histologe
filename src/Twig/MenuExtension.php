<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\Menu\MenuBuilder;
use App\Service\Menu\MenuItem;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    public function __construct(private readonly MenuBuilder $menuBuilder)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('main_menu', [$this, 'getMainMenu']),
        ];
    }

    public function getMainMenu(): MenuItem
    {
        return $this->menuBuilder->build();
    }
}
