<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class BackTagControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);
        $featureSignalementViewEnabled = static::getContainer()->getParameter('feature_signalement_view_enabled');
        if (!$featureSignalementViewEnabled) {
            $this->markTestSkipped('La fonctionnalité "feature_signalement_view_enabled" est désactivée.');
        }
    }

    public function testCreateTagSuccess(): void
    {
        $route = $this->router->generate('back_tag_create', ['uuid' => '00000000-0000-0000-2023-000000000006']);
        $this->client->request('POST', $route, ['new-tag-label' => 'test']);

        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('success', $this->client->getResponse()->getContent());
    }

    public function testCreateTagError(): void
    {
        $route = $this->router->generate('back_tag_create', ['uuid' => '00000000-0000-0000-2023-000000000006']);
        $this->client->request('POST', $route, ['new-tag-label' => 't']);

        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('Le tag doit contenir au moins 2 caract\u00e8res', $this->client->getResponse()->getContent());
    }

    public function testSearchNewTag(): void
    {
        $route = $this->router->generate('back_tags_index');
        $this->client->request('POST', $route, ['territory' => 13, 'search' => 'Urgent']);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('#desc-table', '1 étiquette');
    }

    public function testDeleteNewTagSuccess(): void
    {
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepository->findOneBy(['label' => 'Commission du 12/08', 'isArchive' => 0]);
        $route = $this->router->generate('back_tags_delete');
        $this->client->request(
            'POST',
            $route,
            [
                'tag_id' => $tag->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'tag_delete'),
            ]
        );
        $tag = $tagRepository->findOneBy(['label' => 'Commission du 12/08', 'isArchive' => 0]);
        $this->assertNull($tag);

        $this->client->followRedirect();
        $this->assertSelectorTextContains('#desc-table', '5 étiquettes');
    }

    public function testDeleteNewTagFailed(): void
    {
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepository->findOneBy(['label' => 'Commission du 12/08', 'isArchive' => 0]);
        $route = $this->router->generate('back_tags_delete');
        $this->client->request(
            'POST',
            $route,
            [
                'tag_id' => $tag->getId(),
            ]
        );
        $tag = $tagRepository->findOneBy(['label' => 'Commission du 12/08', 'isArchive' => 0]);
        $this->assertNotNull($tag);

        $this->client->followRedirect();
        $this->assertSelectorTextContains('#desc-table', '6 étiquettes');
    }

    /**
     * @dataProvider provideTagDataForCreateForm
     */
    public function testCreateNewTag(
        string $tagLabel,
        string $tagTerritory,
        bool $withCsrfToken,
        int $codeResponse,
        string $message
    ): void {
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $route = $this->router->generate('back_tags_add');
        $this->client->request(
            'POST',
            $route,
            [
                'label' => $tagLabel,
                'territory' => $tagTerritory,
                '_token' => $withCsrfToken ? $this->generateCsrfToken($this->client, 'add_tag') : 'invalid-token',
            ]
        );

        $this->assertEquals($codeResponse, $this->client->getResponse()->getStatusCode());
        if (Response::HTTP_OK === $codeResponse) {
            $tag = $tagRepository->findOneBy(['label' => 'Moisissure', 'isArchive' => 0, 'territory' => '13']);
            $this->assertNotNull($tag);
        } else {
            $this->assertStringContainsString($message, $this->client->getResponse()->getContent());
        }
    }

    /**
     * @dataProvider provideTagDataForEditForm
     */
    public function testEditNewTag(
        string $tagId,
        string $tagLabel,
        bool $withCsrfToken,
        int $codeResponse,
        string $message
    ): void {
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $route = $this->router->generate('back_tags_edit', ['tag' => $tagId]);
        $this->client->request(
            'POST',
            $route,
            [
                'label' => $tagLabel,
                '_token' => $withCsrfToken ? $this->generateCsrfToken($this->client, 'edit_tag') : 'invalid-token',
            ]
        );

        $this->assertEquals($codeResponse, $this->client->getResponse()->getStatusCode());
        if (Response::HTTP_OK === $codeResponse) {
            $tag = $tagRepository->find($tagId);
            $this->assertEquals('Urgent MAJ', $tag->getLabel());
        } else {
            $this->assertStringContainsString($message, $this->client->getResponse()->getContent());
        }
    }

    public function provideTagDataForCreateForm(): \Generator
    {
        yield 'Success with all data' => [
            'Moisissure',
            '13',
            true,
            Response::HTTP_OK,
            '',
        ];

        yield 'Failed with existing tag label' => [
            'Péril',
            '13',
            true,
            Response::HTTP_BAD_REQUEST,
            'Ce nom d\u0027\u00e9tiquette est d\u00e9j\u00e0 utilis\u00e9. Veuillez saisir une autre nom.',
        ];

        yield 'Failed with empty tag label' => [
            '',
            '13',
            true,
            Response::HTTP_BAD_REQUEST,
            'Merci de saisir un nom pour l\u0027\u00e9tiquette.',
        ];

        yield 'Failed with csrf token missing' => [
            'Moisissure',
            '13',
            false,
            Response::HTTP_BAD_REQUEST,
            'Le jeton CSRF est invalide. Veuillez renvoyer le formulaire.',
        ];
    }

    public function provideTagDataForEditForm(): \Generator
    {
        yield 'Success with all data' => [
            '1',
            'Urgent MAJ',
            true,
            Response::HTTP_OK,
            '',
        ];

        yield 'Failed with empty tag label' => [
            '1',
            '',
            true,
            Response::HTTP_BAD_REQUEST,
            'Merci de saisir un nom pour l\u0027\u00e9tiquette.',
        ];

        yield 'Failed with existing tag label' => [
            '1',
            'Péril',
            true,
            Response::HTTP_BAD_REQUEST,
            'Ce nom d\u0027\u00e9tiquette est d\u00e9j\u00e0 utilis\u00e9. Veuillez saisir une autre nom.',
        ];

        yield 'Failed with csrf token missing' => [
            '1',
            'Urgent Nouveau',
            false,
            Response::HTTP_BAD_REQUEST,
            'Le jeton CSRF est invalide. Veuillez renvoyer le formulaire.',
        ];
    }
}
