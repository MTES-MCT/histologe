<?php

namespace App\Tests\Functional\Controller;

use Faker\Factory;
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

    public function testSubmitContact(): void
    {
        $faker = Factory::create();

        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $client->request('GET', $generatorUrl->generate('front_contact'));

        $client->submitForm('Envoyer le message', [
                'contact[nom]' => 'John Doe',
                'contact[email]' => 'john.doe@yopmail.com',
                'contact[message]' => $faker->text(),
            ]
        );

        $this->assertEmailCount(1);

        $email = $this->getMailerMessage();
        $this->assertEmailHeaderSame($email, 'From', 'HISTOLOGE - ALERTE <notifications@histologe.beta.gouv.fr>');
        $this->assertEmailHeaderSame($email, 'To', 'contact@histologe.beta.gouv.fr');
    }
}
