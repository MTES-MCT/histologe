<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
/**
 * Basculer entre deux configurations de tableau de bord, en se basant sur la valeur de 'FEATURE_LIST_FILTER_ENABLE'.
 * Note : Une fois la nouvelle liste déployée en production, cette conf PHP est à remplacer par la conf YAML.
 */
return function (ContainerConfigurator $configurator) {
    if ((int)$_ENV['FEATURE_LIST_FILTER_ENABLE'] === 1) {
        $configurator->import('widgets_new.yaml');
    } else {
        $configurator->import('widgets.yaml');
    }
};
