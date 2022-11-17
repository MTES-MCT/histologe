<?php

namespace App\Service;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use Exception;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationService
{
    public const TYPE_ACCOUNT_ACTIVATION = 1;
    public const TYPE_ACCOUNT_ACTIVATION_REMINDER = 11;
    public const TYPE_ACCOUNT_DELETE = 14;
    public const TYPE_ACCOUNT_TRANSFER = 15;
    public const TYPE_LOST_PASSWORD = 2;
    public const TYPE_SIGNALEMENT_NEW = 3;
    public const TYPE_ASSIGNMENT_NEW = 4;
    public const TYPE_SIGNALEMENT_VALIDATION = 5;
    public const TYPE_SIGNALEMENT_REFUSAL = 99;
    public const TYPE_SIGNALEMENT_CLOSED_TO_USAGER = 98;
    public const TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS = 97;
    public const TYPE_SIGNALEMENT_CLOSED_TO_PARTNER = 96;
    public const TYPE_CONFIRM_RECEPTION = 6;
    public const TYPE_NEW_COMMENT_FRONT = 7;
    public const TYPE_NEW_COMMENT_BACK = 10;
    public const TYPE_CONTACT_FORM = 8;
    public const TYPE_ERROR_SIGNALEMENT = 9;
    public const TYPE_MIGRATION_PASSWORD = 13;
    public const TYPE_CRON = 100;

    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(int $type, string|array $to, array $params, Territory|null $territory): TransportExceptionInterface|Exception|bool
    {
        $message = $this->renderMailContentWithParamsByType($type, $params, $territory ?? null);
        \is_array($to) ? $emails = $to : $emails = [$to];
        $territoryName = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')
            ->transliterate((!empty($territory) && null !== $territory->getName()) ? $territory->getName() : 'ALERTE');
        foreach ($emails as $email) {
            $email && $message->addTo($email);
        }
        $message->from(new Address('histologe-'.str_replace(' ', '-', mb_strtolower($territoryName)).'@histologe.fr', 'HISTOLOGE - '.mb_strtoupper($territoryName)));
        if (!empty($params['attach'])) {
            $message->attachFromPath($params['attach']);
        }
        try {
            $this->mailer->send($message);

            return true;
        } catch (TransportExceptionInterface $e) {
            return $e;
        }
    }

    private function renderMailContentWithParamsByType(int $type, array $params, Territory|null $territory): NotificationEmail
    {
        $config = $this->config($type, $params);
        $config['territory'] = $territory;
        $notification = new NotificationEmail();
        $notification->markAsPublic();

        return $notification->htmlTemplate('emails/'.$config['template'].'.html.twig')
            ->context(array_merge($params, $config))
            ->subject('HISTOLOGE '.mb_strtoupper((!empty($territory) && null !== $territory->getName()) ? $territory->getName() : 'ALERTE').' - '.$config['subject']);
    }

    private function config(int $type, array $params = []): array
    {
        $reference = $this->getValuePropertySignalementFrom($params, 'reference');

        return match ($type) {
            self::TYPE_ACCOUNT_ACTIVATION => [
                'template' => 'login_link_email',
                'subject' => 'Activez votre compte sur Histologe',
                'btntext' => 'Activer mon compte',
            ],
            self::TYPE_ACCOUNT_ACTIVATION_REMINDER => [
                'template' => 'login_link_email',
                'subject' => 'Activez votre compte sur Histologe',
                'btntext' => 'Activer mon compte',
            ],
            self::TYPE_ACCOUNT_DELETE => [
                'template' => 'delete_account_email',
                'subject' => 'Suppression de votre compte Histologe',
            ],
            self::TYPE_ACCOUNT_TRANSFER => [
                'template' => 'transfer_account_email',
                'subject' => 'Transfert de votre compte Histologe',
            ],
            self::TYPE_LOST_PASSWORD => [
                'template' => 'lost_pass_email',
                'subject' => 'Nouveau mot de passe sur Histologe',
                'btntext' => 'Définir mon mot de passe',
            ],
            self::TYPE_MIGRATION_PASSWORD => [
                'template' => 'migration_pass_email',
                'subject' => 'Transfert de votre compte Histologe',
                'btntext' => 'Définir mon mot de passe',
            ],
            self::TYPE_SIGNALEMENT_NEW => [
                'template' => 'new_signalement_email',
                'subject' => 'Un nouveau signalement vous attend',
                'btntext' => 'Voir le signalement',
            ],
            self::TYPE_ASSIGNMENT_NEW => [
                'template' => 'affectation_email',
                'subject' => 'Un nouveau signalement vous attend sur Histologe',
                'btntext' => 'Accéder au signalement',
            ],
            self::TYPE_SIGNALEMENT_VALIDATION => [
                'template' => 'validation_signalement_email',
                'subject' => 'Votre signalement est validé !',
                'btntext' => 'Suivre mon signalement',
            ],
            self::TYPE_SIGNALEMENT_REFUSAL => [
                'template' => 'refus_signalement_email',
                'subject' => 'Votre signalement ne peut pas être traité.',
            ],
            self::TYPE_SIGNALEMENT_CLOSED_TO_USAGER => [
                'template' => 'closed_to_usager_signalement_email',
                'subject' => 'Votre signalement sur Histologe est terminé.',
                'btnText' => 'Accéder à ma page de suivi',
            ],
            self::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS => [
                'template' => 'closed_to_partners_signalement_email',
                'subject' => 'Le signalement #'.$params['ref_signalement'].' a été cloturé',
                'btnText' => 'Accéder au signalement',
            ],
            self::TYPE_SIGNALEMENT_CLOSED_TO_PARTNER => [
                'template' => 'closed_to_partner_signalement_email',
                'subject' => $params['partner_name'].' a terminé son intervention sur #'.$params['ref_signalement'],
                'btnText' => 'Accéder au signalement',
            ],
            self::TYPE_CONTACT_FORM => [
                'template' => 'nouveau_mail_front',
                'subject' => 'Vous avez reçu un message depuis la page Histologe',
            ],
            self::TYPE_CONFIRM_RECEPTION => [
                'template' => 'accuse_reception_email',
                'subject' => 'Votre signalement a bien été reçu !',
            ],
            self::TYPE_NEW_COMMENT_FRONT => [
                'template' => 'nouveau_suivi_signalement_email',
                'subject' => 'Nouvelle mise à jour de votre signalement !',
                'btntext' => 'Suivre mon signalement',
            ],
            self::TYPE_NEW_COMMENT_BACK => [
                'template' => 'nouveau_suivi_signalement_back_email',
                'subject' => 'Nouveau suivi sur le signalement #'.$reference,
                'btntext' => 'Accéder au signalement',
            ],
            self::TYPE_ERROR_SIGNALEMENT => [
                'template' => 'erreur_signalement_email',
                'subject' => 'Une erreur est survenue lors de la création d\'un signalement !',
            ],
            self::TYPE_CRON => [
                'template' => 'cron_email',
                'subject' => 'La tache planifiée s\'est bien éxécutée.',
            ]
        };
    }

    private function getValuePropertySignalementFrom(array $params, string $value): ?string
    {
        if (isset($params['entity']) && $params['entity'] instanceof Suivi) {
            $suivi = $params['entity'];
            $signalement = $suivi->getSignalement();
            if ($signalement instanceof Signalement && property_exists(Signalement::class, $value)) {
                $getMethod = 'get'.ucfirst($value);

                return $signalement->$getMethod();
            }
        }

        return null;
    }
}
