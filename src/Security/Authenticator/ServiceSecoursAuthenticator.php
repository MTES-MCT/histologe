<?php

namespace App\Security\Authenticator;

use App\Exception\ProConnect\ProConnectException;
use App\Exception\ServiceSecours\ServiceSecoursRouteResolutionException;
use App\Repository\ServiceSecoursRouteRepository;
use App\Security\User\ServiceSecoursUser;
use App\Service\Gouv\Insee\Exception\SireneRateLimitExceededException;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\Request\CallbackRequest;
use App\Service\ServiceSecours\ServiceSecoursRouteResolver;
use App\Utils\UrlHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ServiceSecoursAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const string MESSAGE_SERVICE_INDISPONIBLE = 'Le service est temporairement indisponible. Veuillez réessayer de vous connecter plus tard.';
    public const string MESSAGE_ROUTE_RESOLUTION_ERROR = 'Nous n\'avons pas pu identifier le service de secours correspondant à votre organisation. Veuillez réessayer ou contacter le support.';
    public const string MESSAGE_PROCONNECT_ERROR = 'Erreur lors du traitement de l\'authentification ProConnect.';
    public const string MESSAGE_GENERIC_ERROR = 'Une erreur inattendue est survenue lors de la connexion, veuillez réessayer.';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ProConnectAuthentication $proConnectAuthentication,
        private readonly ServiceSecoursRouteResolver $serviceSecoursRouteResolver,
        private readonly ServiceSecoursRouteRepository $serviceSecoursRouteRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $domain = UrlHelper::extractRootDomain($request->getHost());

        return new RedirectResponse(
            $this->urlGenerator->generate('service_secours_home', [
                'domain' => $domain,
            ])
        );
    }

    public function supports(Request $request): ?bool
    {
        return 'service_secours_proconnect_login_callback' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $code = $request->query->get('code');
            $state = $request->query->get('state');

            $callBackRequest = new CallbackRequest(
                code: is_string($code) ? $code : null,
                state: is_string($state) ? $state : null,
            );

            $proConnectUser = $this->proConnectAuthentication->authenticateFromCallback($callBackRequest);

            $serviceSecoursRoute = $this->serviceSecoursRouteResolver->resolve($proConnectUser);

            $userIdentifier = $proConnectUser->email.':'.$serviceSecoursRoute->getUuid();

            $serviceSecoursUser = new ServiceSecoursUser(
                userIdentifier: $userIdentifier,
                email: $proConnectUser->email,
                serviceSecoursRouteUuid: (string) $serviceSecoursRoute->getUuid(),
                matricule: $proConnectUser->chorusdtMatricule,
                fullname: $proConnectUser->fullname,
            );

            return new SelfValidatingPassport(
                new UserBadge($userIdentifier, static fn () => $serviceSecoursUser)
            );
        } catch (SireneRateLimitExceededException $exception) {
            $this->logger->warning('Quota Sirene dépassé lors du callback ProConnect Service Secours', [
                'error_message' => $exception->getMessage(),
            ]);

            throw new CustomUserMessageAuthenticationException(self::MESSAGE_SERVICE_INDISPONIBLE);
        } catch (ServiceSecoursRouteResolutionException $exception) {
            $this->logger->warning('Erreur de résolution du service de secours callback', [
                'error_message' => $exception->getMessage(),
            ]);

            throw new CustomUserMessageAuthenticationException(self::MESSAGE_ROUTE_RESOLUTION_ERROR);
        } catch (ProConnectException $exception) {
            $this->logger->error('Erreur ProConnect Service Secours callback', [
                'error_message' => $exception->getMessage(),
            ]);

            throw new CustomUserMessageAuthenticationException(self::MESSAGE_PROCONNECT_ERROR);
        } catch (\Throwable $exception) {
            $this->logger->error('Erreur inattendue Service Secours callback', [
                'error_message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw new CustomUserMessageAuthenticationException(self::MESSAGE_GENERIC_ERROR);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $domain = UrlHelper::extractRootDomain($request->getHost());

        if (!$user instanceof ServiceSecoursUser) {
            return new RedirectResponse(
                $this->urlGenerator->generate('service_secours_home', [
                    'domain' => $domain,
                ])
            );
        }

        $serviceSecoursRoute = $this->serviceSecoursRouteRepository->findOneBy([
            'uuid' => $user->getServiceSecoursRouteUuid(),
        ]);

        if (!$serviceSecoursRoute) {
            return new RedirectResponse(
                $this->urlGenerator->generate('service_secours_home', [
                    'domain' => $domain,
                ])
            );
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('service_secours_index', [
                'domain' => $domain,
                'slug' => $serviceSecoursRoute->getSlug(),
                'uuid' => $serviceSecoursRoute->getUuid(),
            ])
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $domain = UrlHelper::extractRootDomain($request->getHost());
        $message = $exception->getMessageKey();
        $flashType = self::MESSAGE_SERVICE_INDISPONIBLE === $message ? 'info' : 'error';

        if ($request->hasSession()) {
            $session = $request->getSession();
            $session->getFlashBag()->add($flashType, $message); // @phpstan-ignore-line
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('service_secours_home', [
                'domain' => $domain,
            ])
        );
    }
}
