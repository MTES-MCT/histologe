<?php

namespace App\Controller\Back;

use App\Entity\Signalement;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class BackTagController extends AbstractController
{
    #[Route('/{uuid}/newtag', name: 'back_tag_create', methods: 'POST')]
    public function createTag(Signalement $signalement, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('TAG_CREATE', null);
        $label = $request->get('new-tag-label');
        if (mb_strlen($label) < 2) {
            return $this->json(['response' => 'error', 'message' => 'Le tag doit contenir au moins 2 caractères']);
        }
        $tag = new Tag();
        $tag->setTerritory($signalement->getTerritory());
        $tag->setLabel($label);
        $entityManager->persist($tag);
        $entityManager->flush();

        return $this->json(['response' => 'success', 'tag' => ['id' => $tag->getId(), 'label' => $tag->getLabel()]]);
    }

    #[Route('/deltag/{id}', name: 'back_tag_delete', defaults: ['id' => null], methods: 'GET')]
    public function deleteTag(Tag $tag, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('TAG_DELETE', $tag);
        $tag->setIsArchive(true);
        $entityManager->persist($tag);
        $entityManager->flush();

        return $this->json(['response' => 'success']);
    }
}
