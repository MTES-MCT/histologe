<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/technique')]
#[IsGranted('ROLE_ADMIN')]
class TechnicalController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'TECHNICAL_ROUTE_ENABLED')]
        private readonly bool $technicalRouteEnabled,
    ) {
        if (!$this->technicalRouteEnabled) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/phpinfos/', name: 'back_technical_phpinfos')]
    public function phpInfos(): Response
    {
        ob_start();
        \phpinfo(); // @phpstan-ignore ekinoBannedCode.function
        $content = ob_get_clean();

        if (false === $content) {
            throw new \RuntimeException('Impossible de récupérer les informations PHP.');
        }

        return new Response($content);
    }
}
