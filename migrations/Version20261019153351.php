<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\ServiceSecoursCategory;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019153351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update service secours category and add unique constraint';
    }

    public function up(Schema $schema): void
    {
        $routes = $this->connection->fetchAllAssociative('SELECT id, territory_id, name FROM service_secours_route ORDER BY id ASC');

        $routesByTerritory = [];
        foreach ($routes as $route) {
            $territoryKey = (string) ($route['territory_id'] ?? 'null');
            $routesByTerritory[$territoryKey][] = $route;
        }

        $allCategories = ServiceSecoursCategory::values();

        foreach ($routesByTerritory as $territoryRoutes) {
            $usedCategories = [];

            foreach ($territoryRoutes as $route) {
                $category = $this->determineCategory((string) $route['name']);

                if (in_array($category, $usedCategories, true)) {
                    $available = array_values(array_diff($allCategories, $usedCategories));
                    if (empty($available)) {
                        continue;
                    }
                    $category = $available[0];
                }

                $usedCategories[] = $category;

                $this->connection->update(
                    'service_secours_route',
                    ['category' => $category],
                    ['id' => $route['id']]
                );
            }
        }

        $this->addSql('ALTER TABLE service_secours_route CHANGE category category VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_territory_category ON service_secours_route (territory_id, category)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_territory_category ON service_secours_route');
        $this->addSql('ALTER TABLE service_secours_route CHANGE category category VARCHAR(50) DEFAULT NULL');
    }

    private function determineCategory(string $name): string
    {
        if (preg_match('/(bmpm|marin)/i', $name)) {
            return ServiceSecoursCategory::MARINS_POMPIERS->value;
        }
        if (preg_match('/(gendarmerie|gendarme|brigade)/i', $name)) {
            return ServiceSecoursCategory::GENDARMERIE->value;
        }
        if (preg_match('/(police|commissariat|crs)/i', $name)) {
            return ServiceSecoursCategory::POLICE->value;
        }
        if (preg_match('/(sdis|pompier|sapeur|secours|incendie|feu)/i', $name)) {
            return ServiceSecoursCategory::SDIS->value;
        }

        return ServiceSecoursCategory::AUTRE->value;
    }
}
