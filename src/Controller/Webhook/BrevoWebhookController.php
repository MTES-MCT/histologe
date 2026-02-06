<?php

namespace App\Controller\Webhook;

use App\Entity\EmailDeliveryIssue;
use App\Entity\Enum\BrevoEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sentry\Severity;
use Sentry\State\Scope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BrevoWebhookController extends AbstractController
{
    /**
     * @var string[]
     */
    private array $allowedIps;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        #[Autowire(env: 'BREVO_ALLOWED_IPS')] string $allowedIps,
    ) {
        $this->allowedIps = array_filter(array_map('trim', explode(',', $allowedIps)));
        $this->entityManager = $entityManager;
    }

    #[Route('/webhook/brevo', name: 'webhook_brevo', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $clientIp = $request->getClientIp();
        if (!$this->isAllowedIp($clientIp)) {
            return new Response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $event = $payload['event'] ?? null;
        $email = isset($payload['email']) ? mb_strtolower(trim($payload['email'])) : null;
        if (!$event || !$email) {
            return new Response('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        $isDeliveryFailure = BrevoEvent::isErrorEvent($event);

        $emailDeliveryIssueRepository = $this->entityManager->getRepository(EmailDeliveryIssue::class);
        if (!$this->emailExistsInSystem($email)) {
            \Sentry\configureScope(function (Scope $scope) use ($email, $payload): void {
                $scope->setTag('email_recipient', $email);
                $scope->setExtra('brevo_payload', $payload);
            });

            $severity = match ($event) {
                BrevoEvent::BLOCKED->value => new Severity(Severity::FATAL),
                BrevoEvent::HARD_BOUNCE, BrevoEvent::SOFT_BOUNCE, BrevoEvent::SPAM, BrevoEvent::INVALID_EMAIL, BrevoEvent::ERROR => new Severity(Severity::ERROR),
                default => null,
            };

            if ($severity) {
                \Sentry\captureMessage("[BREVO] Email: {$event}", $severity);
            }

            return new Response('OK', Response::HTTP_OK);
        }

        if ($isDeliveryFailure) {
            $emailDeliveryIssue = $emailDeliveryIssueRepository->findOneBy(['email' => $email]) ?? new EmailDeliveryIssue();
            $emailDeliveryIssue
                ->setEmail($email)
                ->setEvent(BrevoEvent::tryFrom($event))
                ->setReason($payload['reason'] ?? null)
                ->setPayload($payload);

            $this->entityManager->persist($emailDeliveryIssue);
            $this->entityManager->flush();
        } elseif (BrevoEvent::isSuccessEvent($event)) {
            if ($emailDeliveryIssue = $emailDeliveryIssueRepository->findOneBy(['email' => $email])) {
                $this->entityManager->remove($emailDeliveryIssue);
                $this->entityManager->flush();
            }
        }

        return new Response('OK', Response::HTTP_OK);
    }

    private function emailExistsInSystem(string $email): bool
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        // Cherche l'email exact
        $user = $userRepository->findOneBy(['email' => $email]);
        if ($user) {
            return true;
        }

        $partner = $partnerRepository->findOneBy(['email' => $email]);
        if ($partner) {
            return true;
        }

        // Cherche les utilisateurs ou partenaires archivés
        $pattern = User::SUFFIXE_ARCHIVED;
        $emailPrefix = explode($pattern, $email)[0];

        $user = $userRepository->createQueryBuilder('u')
            ->where('u.email LIKE :emailPrefix')
            ->setParameter('emailPrefix', $emailPrefix.'%')
            ->getQuery()
            ->getOneOrNullResult();

        if ($user) {
            return true;
        }

        $partner = $partnerRepository->createQueryBuilder('p')
            ->where('p.email LIKE :emailPrefix')
            ->setParameter('emailPrefix', $emailPrefix.'%')
            ->getQuery()
            ->getOneOrNullResult();

        if ($partner) {
            return true;
        }

        // Cherche parmi les mails du signalement
        $qb = $signalementRepository->createQueryBuilder('s');
        $qb->select('1')
            ->where('s.mailOccupant = :email')
            ->orWhere('s.mailDeclarant = :email')
            ->orWhere('s.mailProprio = :email')
            ->orWhere('s.mailAgence = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1);

        if ($qb->getQuery()->getOneOrNullResult()) {
            return true;
        }

        return false;
    }

    private function isAllowedIp(?string $ip): bool
    {
        if (null === $ip) {
            return false;
        }
        foreach ($this->allowedIps as $cidr) {
            if ($this->ipInRange($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }
}
