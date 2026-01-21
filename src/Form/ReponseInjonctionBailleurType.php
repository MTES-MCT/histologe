<?php

namespace App\Form;

use App\Dto\ReponseInjonctionBailleur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ReponseInjonctionBailleurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reponse', ChoiceType::class, [
                'label' => 'Engagement à mettre le logement en conformité',
                'help' => 'Indiquez si vous reconnaissez les désordres déclarés et si vous vous engagez à réaliser les travaux nécessaires pour les résoudre.',
                'choices' => [
                    'Oui' => ReponseInjonctionBailleur::REPONSE_OUI,
                    'Oui avec aide' => ReponseInjonctionBailleur::REPONSE_OUI_AVEC_AIDE,
                    'Oui, les démarches ont commencé' => ReponseInjonctionBailleur::REPONSE_OUI_DEMARCHES_COMMENCEES,
                    'Non' => ReponseInjonctionBailleur::REPONSE_NON,
                ],
                'required' => false,
                'placeholder' => false,
                'expanded' => true,
                'choice_label' => false, // on gère les labels dans le template
                'choice_attr' => function (?string $choice, ?string $key, ?string $value) {
                    return match ($value) {
                        ReponseInjonctionBailleur::REPONSE_OUI => [
                            'data-dsfr-label' => 'Oui, je m\'engage à réaliser les travaux nécessaires',
                            'data-dsfr-hint' => null,
                        ],
                        ReponseInjonctionBailleur::REPONSE_OUI_AVEC_AIDE => [
                            'data-dsfr-label' => 'Oui, je m\'engage à réaliser les travaux nécessaires et j\'ai besoin d\'un accompagnement',
                            'data-dsfr-hint' => 'Les services de l\'ADIL et de France Rénov pourront vous accompagner',
                        ],
                        ReponseInjonctionBailleur::REPONSE_OUI_DEMARCHES_COMMENCEES => [
                            'data-dsfr-label' => 'Oui, je m\'engage à réaliser les travaux nécessaires et j\'ai déjà commencé les démarches',
                            'data-dsfr-hint' => 'Les travaux ont déjà débuté ou les devis sont en cours',
                        ],
                        ReponseInjonctionBailleur::REPONSE_NON => [
                            'data-dsfr-label' => 'Non, je conteste les désordres déclarés et ne m\'engage pas à réaliser de travaux',
                            'data-dsfr-hint' => 'Le dossier sera transmis aux autorités compétentes.',
                        ],
                        default => [],
                    };
                },
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Commentaire',
                'label_attr' => [
                    'data-label-oui' => 'Souhaitez-vous apporter des précisions sur la situation ? (facultatif)',
                    'data-label-oui-avec-aide' => 'Précisez votre situation et l\'aide attendue',
                    'data-label-oui-demarches-commencees' => 'Précisez les démarches que vous avez engagées',
                    'data-label-non' => 'Précisez la raison de votre refus',
                ],
                'help' => 'Dix (10) caractères minimum',
                'attr' => [
                    'rows' => 5,
                ],
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Confirmer ma réponse',
                'attr' => [
                    'class' => 'fr-btn--primary',
                ],
            ]);
    }
}
