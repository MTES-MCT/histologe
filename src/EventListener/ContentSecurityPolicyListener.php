<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ContentSecurityPolicyListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // Générer des nonces pour les scripts et les styles
        $scriptNonce = bin2hex(random_bytes(16));
        $styleNonce = bin2hex(random_bytes(16));
        // $scriptNonce = $response->headers->get('script-nonce') ?? bin2hex(random_bytes(16));
        // $styleNonce = $response->headers->get('style-nonce') ?? bin2hex(random_bytes(16));

        // Ajouter les nonces aux attributs de la requête pour les utiliser dans les vues
        $request->attributes->set('csp_script_nonce', $scriptNonce);
        $request->attributes->set('csp_style_nonce', $styleNonce);
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Extract the nonces from the response (assuming nonces are set as headers)
        $scriptNonce = $request->attributes->get('csp_script_nonce');
        $styleNonce = $request->attributes->get('csp_style_nonce');

        // Générer des nonces pour les scripts et les styles
        // $scriptNonce = bin2hex(random_bytes(16));
        // $styleNonce = bin2hex(random_bytes(16));

        // Créer la politique CSP avec les nonces
        $csp = "default-src 'none'; ".
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' 'nonce-$scriptNonce' https://cdn.matomo.cloud/histologe.matomo.cloud/matomo.js https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.js  https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js https://cdn.jsdelivr.net/npm/tippy.js@6/dist/tippy-bundle.umd.js; ".
               "style-src 'self' 'unsafe-inline' 'nonce-$styleNonce' https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.css https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.css https://cdn.jsdelivr.net/npm/tippy.js@6/themes/light-border.css; ".
               "style-src-attr 'self' 'unsafe-inline';".
               "img-src 'self' data: blob: https://voxusagers.numerique.gouv.fr https://*.tile.openstreetmap.org https://cdn.jsdelivr.net https://jedonnemonavis.numerique.gouv.fr; ".
               "connect-src 'self' https://api-adresse.data.gouv.fr https://cdn.matomo.cloud https://histologe.matomo.cloud https://koumoul.com https://sentry.incubateur.net; ".
               "font-src 'self'; ".
               "frame-src 'none'; ".
               "object-src 'none'; ".
               "base-uri 'self'; ".
               "form-action 'self'; ".
               "frame-ancestors 'none'; ".
               "media-src 'self'; ".
               "require-trusted-types-for 'script';";

        // Ajouter la politique CSP aux en-têtes de la réponse
        $response->headers->set('Content-Security-Policy', $csp);

        // // Ajouter les nonces aux attributs de la requête pour les utiliser dans les vues
        // $event->getRequest()->attributes->set('csp_script_nonce', $scriptNonce);
        // $event->getRequest()->attributes->set('csp_style_nonce', $styleNonce);
    }
}
