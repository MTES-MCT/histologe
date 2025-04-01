<?php

namespace App\Tests\Functional\Controller;

use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HomepageControllerTest extends WebTestCase
{
    public function testSubmitWithValidPostcode(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('home'));

        $client->submitForm('Signaler mon problème', [
            'postal_code_search[postalcode]' => '13002',
        ]
        );

        $this->assertResponseRedirects('/signalement?cp=13002');
    }

    public function testSubmitWithEmptyPostcode(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('home'));

        $client->submitForm('Signaler mon problème', [
            'postal_code_search[postalcode]' => '',
        ]
        );

        $this->assertResponseIsSuccessful('Empty postal code must not be submitted');
    }

    public function testDisplayGitBookFaqExternalLink(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $client->request('GET', $generatorUrl->generate('home'));

        $this->assertSelectorTextContains('#submenu-help ul', 'Documentation');
        $link = $crawler->selectLink('Documentation')->link();
        $this->assertEquals('https://faq.signal-logement.beta.gouv.fr', $link->getUri());
    }

    public function testSubmitContactWithValidData(): void
    {
        $faker = Factory::create();

        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('front_contact'));

        $client->submitForm('Envoyer le message', [
            'contact[nom]' => 'John Doe',
            'contact[email]' => 'john.doe@yopmail.com',
            'contact[message]' => $faker->text(),
            'contact[organisme]' => '',
            'contact[objet]' => 'Autre',
        ]
        );

        $client->enableProfiler();
        $this->assertEmailCount(1);

        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $email = $this->getMailerMessage();
        $this->assertEmailHeaderSame($email, 'From', 'SIGNAL-LOGEMENT - ALERTE <'.$parameterBag->get('notifications_email').'>');
        $this->assertEmailHeaderSame($email, 'To', $parameterBag->get('contact_email'));
    }

    public function testSubmitContactWithEmptyMessage(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('front_contact'));

        $client->submitForm('Envoyer le message', [
            'contact[nom]' => 'John Doe',
            'contact[email]' => 'john.doe@y@opmail.com',
            'contact[message]' => '',
            'contact[organisme]' => '',
            'contact[objet]' => 'Autre',
        ]
        );

        $response = $client->getResponse();
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('html', $data);
        $this->assertStringContainsString('Merci de renseigner votre message', $data['html']);
    }

    public function testSubmitDemandeLienSignalement(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('home'));

        $client->submitForm('demande_lien_signalement_save', [
            'demande_lien_signalement[email]' => 'francis.cabrel@astaffort.com',
            'demande_lien_signalement[adresseHelper]' => '3 rue Mars 13015 Marseille',
            'demande_lien_signalement[adresse]' => '3 rue Mars',
            'demande_lien_signalement[codePostal]' => '13015',
            'demande_lien_signalement[ville]' => 'Marseille',
        ]);

        $this->assertEmailCount(1);
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Si un signalement correspond aux informations saisies', $responseContent['html']);
    }
}
