<?php

namespace App\Service;

use App\Entity\Territory;
use Exception;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationService
{
    const TYPE_ACCOUNT_ACTIVATION = 1;
    const TYPE_ACCOUNT_ACTIVATION_REMINDER = 11;
    const TYPE_LOST_PASSWORD = 2;
    const TYPE_SIGNALEMENT_NEW = 3;
    const TYPE_ASSIGNMENT_NEW = 4;
    const TYPE_SIGNALEMENT_VALIDATION = 5;
    const TYPE_SIGNALEMENT_REFUSAL = 99;
    const TYPE_CONFIRM_RECEPTION = 6;
    const TYPE_NEW_COMMENT_FRONT = 7;
    const TYPE_NEW_COMMENT_BACK = 10;
    const TYPE_CONTACT_FORM = 8;
    const TYPE_ERROR_SIGNALEMENT = 9;

    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(int $type, string|array $to, array $params, Territory|null $territory): TransportExceptionInterface|Exception|bool
    {
        $params['url'] = $_SERVER['SERVER_NAME'] ?? null;
        $message = $this->renderMailContentWithParamsByType($type, $params, $territory ?? null);
        is_array($to) ? $emails = $to : $emails = [$to];
        $territoryName = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')
            ->transliterate((!empty($territory) && null !== $territory->getName())?$territory->getName():'ALERTE');
        foreach ($emails as $email)
            $email && $message->addTo($email);
        $message->from(new Address('histologe-' . str_replace(' ', '-', mb_strtolower($territoryName)) . '@histologe.fr', 'HISTOLOGE - ' . mb_strtoupper($territoryName)));
        if (!empty($params['attach']))
            $message->attachFromPath($params['attach']);
        if (!empty($territory) && null !== $territory->getConfig() && $territory->getConfig()?->getEmailReponse() ?? isset($params['reply']))
            $message->replyTo($params['reply'] ?? $territory->getConfig()->getEmailReponse());
        try {
            $this->mailer->send($message);
            return true;
        } catch (TransportExceptionInterface $e) {
            return $e;
        }
    }

    private function renderMailContentWithParamsByType(int $type, array $params, Territory|null $territory): NotificationEmail
    {

        $config = $this->config($type);
        $notification = new NotificationEmail();
        $notification->markAsPublic();
        return $notification->htmlTemplate('emails/' . $config['template'] . '.html.twig')
            ->context(array_merge($params, $config))
            ->subject('HISTOLOGE ' . mb_strtoupper((!empty($territory) && null !== $territory->getName())?$territory->getName():'ALERTE') . ' - ' . $config['subject']);
    }

    private function config(int $type): array
    {
        return match ($type) {
            NotificationService::TYPE_ACCOUNT_ACTIVATION => [
                'template' => 'login_link_email',
                'subject' => 'Activation de votre compte',
                'btntext' => "J'active mon compte"
            ],
            NotificationService::TYPE_ACCOUNT_ACTIVATION_REMINDER => [
                'template' => 'login_link_email',
                'subject' => 'Vous n\'avez toujours pas activer votre compte',
                'btntext' => "J'active mon compte"
            ],
            NotificationService::TYPE_LOST_PASSWORD => [
                'template' => 'lost_pass_email',
                'subject' => 'R??cup??ration de votre mot de passe',
                'btntext' => "Je cr??er un nouveau mot de passe"
            ],
            NotificationService::TYPE_SIGNALEMENT_NEW => [
                'template' => 'new_signalement_email',
                'subject' => 'Un nouveau signalement vous attend',
                'btntext' => "Voir le signalement"
            ],
            NotificationService::TYPE_ASSIGNMENT_NEW => [
                'template' => 'affectation_email',
                'subject' => 'Vous avez ??t?? affect?? ?? un signalement',
                'btntext' => "Voir le signalement"
            ],
            NotificationService::TYPE_SIGNALEMENT_VALIDATION => [
                'template' => 'validation_signalement_email',
                'subject' => 'Votre signalement est valid?? !',
                'btntext' => "Suivre mon signalement"
            ],
            NotificationService::TYPE_SIGNALEMENT_REFUSAL => [
                'template' => 'refus_signalement_email',
                'subject' => 'Votre signalement ne peut pas ??tre trait??.',
            ],
            NotificationService::TYPE_CONTACT_FORM => [
                'template' => 'nouveau_mail_front',
                'subject' => 'Vous avez re??u un message depuis la page Histologe',
            ],
            NotificationService::TYPE_CONFIRM_RECEPTION => [
                'template' => 'accuse_reception_email',
                'subject' => 'Votre signalement a bien ??t?? re??u !',
            ],
            NotificationService::TYPE_NEW_COMMENT_FRONT => [
                'template' => 'nouveau_suivi_signalement_email',
                'subject' => 'Nouvelle mise ?? jour de votre signalement !',
                'btntext' => "Suivre mon signalement"
            ],
            NotificationService::TYPE_NEW_COMMENT_BACK => [
                'template' => 'nouveau_suivi_signalement_back_email',
                'subject' => 'Nouveau suivi sur un signalement',
                'btntext' => "Consulter sur la plateforme"
            ],
            NotificationService::TYPE_ERROR_SIGNALEMENT => [
                'template' => 'erreur_signalement_email',
                'subject' => 'Une erreur est survenue lors de la cr??ation d\'un signalement !',
            ]
        };
    }
}
