<?php

namespace App\EventListener;

use Random\RandomException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

readonly class ContentSecurityPolicyListener
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @throws RandomException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $scriptNonce = bin2hex(random_bytes(16));

        $request->attributes->set('csp_script_nonce', $scriptNonce);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if ('/logout' === $request->getPathInfo()) {
            $response->headers->set('Content-Security-Policy', "default-src 'none';");

            return;
        }

        $scriptNonce = $request->attributes->get('csp_script_nonce');

        $cspParameters = $this->parameterBag->get('csp_parameters');

        if ($this->isMockEnvironment()) {
            $proConnectDomain = $this->parameterBag->get('proconnect_domain');
            $formActionDirectives = $this->formatCspDirective($cspParameters['form-action'] ?? []);
            $cspParameters['form-action'] = explode(' ', str_replace($proConnectDomain, 'localhost:8082', $formActionDirectives));
        }

        $cspDirectives = [
            'default-src' => $this->formatCspDirective($cspParameters['default-src'] ?? []),
            'manifest-src' => $this->formatCspDirective($cspParameters['manifest-src'] ?? []),
            'script-src' => $this->formatCspDirective([...($cspParameters['script-src'] ?? []), "'nonce-$scriptNonce'"]),
            'style-src' => $this->formatCspDirective($cspParameters['style-src'] ?? []),
            'style-src-attr' => $this->formatCspDirective($cspParameters['style-src-attr'] ?? []),
            'img-src' => $this->formatCspDirective($cspParameters['img-src'] ?? []),
            'worker-src' => $this->formatCspDirective($cspParameters['worker-src'] ?? []),
            'connect-src' => $this->formatCspDirective($cspParameters['connect-src'] ?? []),
            'font-src' => $this->formatCspDirective($cspParameters['font-src'] ?? []),
            'frame-src' => $this->formatCspDirective($cspParameters['frame-src'] ?? []),
            'object-src' => $this->formatCspDirective($cspParameters['object-src'] ?? []),
            'base-uri' => $this->formatCspDirective($cspParameters['base-uri'] ?? []),
            'form-action' => $this->formatCspDirective($cspParameters['form-action'] ?? []),
            'frame-ancestors' => $this->formatCspDirective($cspParameters['frame-ancestors'] ?? []),
            'media-src' => $this->formatCspDirective($cspParameters['media-src'] ?? []),
            'report-uri' => $this->formatCspDirective($cspParameters['report-uri'] ?? []),
        ];

        $csp = $this->buildCspHeader($cspDirectives);

        if ($this->parameterBag->get('csp_enable')) {
            $response->headers->set('Content-Security-Policy', $csp);
        }
    }

    /**
     * @param array<string> $directive
     */
    private function formatCspDirective(array $directive): string
    {
        return implode(' ', $directive);
    }

    /**
     * @param array<string, string> $directives
     */
    private function buildCspHeader(array $directives): string
    {
        $csp = '';

        foreach ($directives as $directive => $values) {
            if (!empty($values)) {
                $csp .= $directive.' '.$values.'; ';
            }
        }

        return rtrim($csp, ' ');
    }

    private function isMockEnvironment(): bool
    {
        return str_contains($this->parameterBag->get('proconnect_domain'), 'wiremock');
    }
}
