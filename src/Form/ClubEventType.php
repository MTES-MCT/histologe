<?php

namespace App\Form;

use App\Entity\ClubEvent;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Form\Type\SearchCheckboxEnumType;
use App\Service\TimezoneProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ClubEventType extends AbstractType
{
    public function __construct(private readonly TimezoneProvider $timezoneProvider)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $timezoneProvider = $this->timezoneProvider;

        $builder->add('name', null, [
            'label' => 'Nom de l\'événement',
            'help' => '50 caractères maximum.',
            'required' => false,
        ]);
        $builder->add('url', null, [
            'label' => 'URL de l\'événement',
            'help' => '255 caractères maximum.',
            'required' => false,
        ]);
        $builder->add('dateEvent', null, [
            'label' => 'Date de l\'événement',
            'widget' => 'single_text',
            'required' => false,
            'getter' => function (ClubEvent $clubEvent) use ($timezoneProvider): ?\DateTimeInterface {
                $date = $clubEvent->getDateEvent();
                if (null === $date) {
                    return null;
                }

                return (clone $date)->setTimezone($timezoneProvider->getDateTimezone());
            },
            'setter' => function (ClubEvent $clubEvent, ?\DateTimeInterface $date) use ($timezoneProvider): void {
                if (null === $date) {
                    return;
                }
                $utcDate = new \DateTimeImmutable(
                    $date->format('Y-m-d H:i:s'),
                    $timezoneProvider->getDateTimezone()
                );
                $utcDate = $utcDate->setTimezone(new \DateTimeZone('UTC'));
                $clubEvent->setDateEvent($utcDate);
            },
        ]);
        $builder->add('userRoles', ChoiceType::class, [
            'label' => 'Utilisateurs concernés',
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'choices' => [
                'Resp. Territoire' => 'ROLE_ADMIN_TERRITORY',
                'Admin. partenaire' => 'ROLE_ADMIN_PARTNER',
                'Agent' => 'ROLE_USER_PARTNER',
            ],
        ]);
        $builder->add('partnerTypes', SearchCheckboxEnumType::class, [
            'class' => PartnerType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'label' => 'Type de partenaire (facultatif)',
            'noselectionlabel' => 'Choisissez le ou les types de partenaires dans la liste',
            'nochoiceslabel' => 'Aucun type de partenaire disponible',
            'help' => 'Choisissez un ou plusieurs types de partenaire parmi la liste ci-dessous.',
            'required' => false,
        ]);
        $builder->add('partnerCompetences', SearchCheckboxEnumType::class, [
            'class' => Qualification::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'label' => 'Compétences (facultatif)',
            'noselectionlabel' => 'Choisissez la ou les compétences dans la liste',
            'nochoiceslabel' => 'Aucune compétence disponible',
            'help' => 'Choisissez une ou plusieurs compétences parmi la liste ci-dessous.',
            'required' => false,
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Valider',
            'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
            'row_attr' => ['class' => 'fr-text--right'],
        ]);
    }
}
