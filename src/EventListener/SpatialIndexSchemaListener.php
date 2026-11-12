<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
class SpatialIndexSchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $platform = $args->getEntityManager()->getConnection()->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $schema = $args->getSchema();
            if ($schema->hasTable('zone')) {
                $table = $schema->getTable('zone');
                if ($table->hasIndex('idx_area_spatial')) {
                    $table->dropIndex('idx_area_spatial');
                }
            }
        }
    }
}
