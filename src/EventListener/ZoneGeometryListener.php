<?php

namespace App\EventListener;

use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postLoad)]
class ZoneGeometryListener
{
    public function postLoad(PostLoadEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Zone) {
            return;
        }

        // Convert GEOMETRY binary data to WKT text for display
        $em = $args->getObjectManager();
        $conn = $em->getConnection();

        $wktArea = $conn->executeQuery(
            'SELECT ST_AsText(area) as area_wkt FROM zone WHERE id = :id',
            ['id' => $entity->getId()]
        )->fetchOne();

        if ($wktArea) {
            $entity->setArea($wktArea);
        }
    }
}
