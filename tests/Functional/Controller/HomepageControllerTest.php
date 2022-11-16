<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HomepageControllerTest extends WebTestCase
{
    public function testDisplayGitBookFaqExternalLink(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $client->request('GET', $generatorUrl->generate('home'));

        $this->assertSelectorTextContains('.fr-header__tools-links ul', 'Aide');
        $link = $crawler->selectLink('Aide')->link();
        $this->assertEquals('https://faq.histologe.beta.gouv.fr', $link->getUri());
    }
}
