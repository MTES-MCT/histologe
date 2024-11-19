<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class QuestionControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
    }

    /** @dataProvider provideProfil */
    public function testGetQuestionProfil(string $profil): void
    {
        $route = $this->router->generate('public_api_question_profile');
        $this->client->request('GET', $route, ['profil' => $profil]);

        $this->assertResponseIsSuccessful();
    }

    public function provideProfil(): \Generator
    {
        yield 'tous' => ['tous'];
        yield 'locataire' => ['locataire'];
        yield 'bailleur_occupant' => ['bailleur_occupant'];
        yield 'tiers_pro' => ['tiers_pro'];
        yield 'tiers_particulier' => ['tiers_particulier'];
        yield 'service_secours' => ['service_secours'];
    }
}
