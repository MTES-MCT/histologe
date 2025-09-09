<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Enum\DocumentType;
use App\Repository\FileRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class AdminTerritoryFilesControllerTest extends WebTestCase
{
    use SessionHelper;

    /**
     * Teste les erreurs d'ajout d'un fichier via la méthode addAjax.
     */
    public function testAddAjaxFails(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $router = self::getContainer()->get(RouterInterface::class);
        $crawler = $client->request('GET', $router->generate('back_territory_management_document_add'));
        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form();

        $formValues = $form->getPhpValues();
        $formName = array_key_first($formValues);
        $formValues[$formName]['title'] = 'Titre de test';
        $formValues[$formName]['description'] = 'Description de test';
        $formValues[$formName]['documentType'] = DocumentType::AUTRE->value;

        $route = $router->generate('back_territory_management_document_add_ajax');
        $client->request('POST', $route, $formValues);

        // Erreur d'absence de fichier en retour json
        $response = $client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $json = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('errors', $json);
    }

    /**
     * Teste la modification de title d'un fichier via editAjax.
     */
    public function testEditAjaxModifiesTitle(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var FileRepository $fileRepository */
        $fileRepository = static::getContainer()->get(FileRepository::class);
        $file = $fileRepository->findOneBy(['isStandalone' => true]);
        $this->assertNotNull($file, 'Aucun fichier disponible pour le test.');

        $router = self::getContainer()->get(RouterInterface::class);

        // On récupère le formulaire d'édition pour obtenir les champs attendus
        $crawler = $client->request('GET', $router->generate('back_territory_management_document_edit', ['file' => $file->getId()]));
        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form();

        $newTitle = 'Titre modifié par test';
        $formValues = $form->getPhpValues();
        // On suppose que le champ description existe dans le formulaire
        $formName = array_key_first($formValues);
        $formValues[$formName]['title'] = $newTitle;

        $route = $router->generate('back_territory_management_document_edit_ajax', ['file' => $file->getId()]);
        $client->request('POST', $route, $formValues);

        // Après redirection, on vérifie que la description a bien été modifiée en base
        /** @var FileRepository $fileRepository */
        $fileRepository = static::getContainer()->get(FileRepository::class);
        $fileRefreshed = $fileRepository->find($file->getId());
        $this->assertEquals($newTitle, $fileRefreshed->getTitle());
    }

    /**
     * Teste la suppression réussie d'un fichier avec un token CSRF valide.
     */
    public function testDeleteSuccess(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var FileRepository $fileRepository */
        $fileRepository = static::getContainer()->get(FileRepository::class);
        $file = $fileRepository->findOneBy(['filename' => '1_Demande_de_transmission_d_une_copie_d_un_DPE.docx']);
        $this->assertNotNull($file, 'Aucun fichier disponible pour le test.');

        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_territory_management_document_delete_ajax', ['file' => $file->getId()]);

        $csrfToken = $this->generateCsrfToken($client, 'document_delete');

        $client->request('GET', $route.'?_token='.$csrfToken);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorExists('.fr-alert--success');
    }

    /**
     * Teste l'échec de suppression d'un fichier (GET avec token CSRF invalide).
     */
    public function testDeleteWithInvalidCsrf(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var FileRepository $fileRepository */
        $fileRepository = static::getContainer()->get(FileRepository::class);
        $file = $fileRepository->findOneBy(['isStandalone' => true]);

        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_territory_management_document_delete_ajax', ['file' => $file->getId()]);
        $client->request('GET', $route.'?_token=invalid');

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorExists('.fr-alert--error');
    }
}
