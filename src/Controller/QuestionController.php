<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/api')]
class QuestionController extends AbstractController
{
    public const string JSON_BASE_PATH = '/../../public/build/json/Signalement/';

    #[Route('/dictionary', name: 'public_api_dictionary')]
    public function getDictionary(): Response
    {
        $filepath = self::JSON_BASE_PATH.'dictionary.json';

        return $this->json(json_decode(file_get_contents(__DIR__.$filepath), true));
    }

    #[Route('/questions', name: 'public_api_question_profile')]
    public function getQuestion(Request $request): Response
    {
        switch ($request->query->get('profil')) {
            case 'tous':
                $filepath = self::JSON_BASE_PATH.'questions_profile_tous.json';
                break;
            case 'locataire':
                $filepath = self::JSON_BASE_PATH.'questions_profile_locataire.json';
                break;
            case 'bailleur_occupant':
                $filepath = self::JSON_BASE_PATH.'questions_profile_bailleur_occupant.json';
                break;
            case 'tiers_pro':
                $filepath = self::JSON_BASE_PATH.'questions_profile_tiers_pro.json';
                break;
            case 'tiers_particulier':
                $filepath = self::JSON_BASE_PATH.'questions_profile_tiers_particulier.json';
                break;
            case 'service_secours':
                $filepath = self::JSON_BASE_PATH.'questions_profile_service_secours.json';
                break;
            case 'bailleur':
                $filepath = self::JSON_BASE_PATH.'questions_profile_bailleur.json';
                break;
            default:
                $filepath = self::JSON_BASE_PATH.'empty.json';
        }

        return $this->json(json_decode(file_get_contents(__DIR__.$filepath), true));
    }

    #[Route('/desordres', name: 'public_api_desordres_profile')]
    public function getDesordres(Request $request): Response
    {
        switch ($request->query->get('profil')) {
            case 'locataire':
            case 'bailleur_occupant':
                $filepath = self::JSON_BASE_PATH.'desordres_profile_occupant.json';
                break;
            case 'tiers_pro':
            case 'tiers_particulier':
            case 'service_secours':
            case 'bailleur':
                $filepath = self::JSON_BASE_PATH.'desordres_profile_tiers.json';
                break;
            default:
                $filepath = self::JSON_BASE_PATH.'empty.json';
        }

        return $this->json(json_decode(file_get_contents(__DIR__.$filepath), true));
    }
}
