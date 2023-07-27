<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class QuestionController extends AbstractController
{
    #[Route('/questions', name: 'api_question_profile')]
    public function getQuestion(Request $request): Response
    {
        $filepath = 'tous' === $request->query->get('profil')
            ? '/../../tools/wiremock/src/Resources/Signalement/questions_profile_tous.json'
            : '/../../tools/wiremock/src/Resources/Signalement/questions_profile_locataire.json';

        return $this->json(json_decode(file_get_contents(__DIR__.$filepath), true));
    }
}
