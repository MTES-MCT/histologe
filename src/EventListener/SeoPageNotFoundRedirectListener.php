<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class SeoPageNotFoundRedirectListener
{
    public const SEO_URL_MAPPING = [
        '/Home' => 'home',
        '/Aide' => 'front_aides_travaux',
        '/Territoires' => 'front_about',
        '/Contact' => 'front_contact',
        '/Statistiques' => 'front_statistiques',
        '/statistiques' => 'front_statistiques',
        '/Chiffres' => 'front_statistiques',
        '/CGU' => 'front_cgu',
    ];

    public function __construct(private RouterInterface $router)
    {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $uri = $request->getRequestUri();
        if (\array_key_exists($uri, self::SEO_URL_MAPPING)) {
            $url = $this->router->generate(self::SEO_URL_MAPPING[$uri]);
            $response = new RedirectResponse($url);
            $response->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);
            $event->setResponse($response);
        }
    }
}
