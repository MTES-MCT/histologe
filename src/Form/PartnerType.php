<?php

namespace App\Form;

use App\Entity\Bailleur;
use App\Entity\Enum\PartnerType as EnumPartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Form\Type\SearchCheckboxEnumType;
use App\Repository\BailleurRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PartnerType extends AbstractType
{
    private $isAdmin = false;
    private $isAdminTerritory = false;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly UserRepository $userRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly Security $security,
    ) {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $this->isAdminTerritory = true;
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $territory = false;
        $insee = $this->parameterBag->get('authorized_codes_insee');
        /** @var Partner $partner */
        $partner = $builder->getData();
        $territory = $options['data']->getTerritory();
        // dump($territory);

        // $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder) {
        //     dump('PRE_SET_DATA');
        //     dump($event);
        //     dump($builder);
        //     $this->addBailleurSocialField(
        //         $event->getForm(),
        //         $builder->getData()->getTerritory()
        //     );
        //     // $this->desactivePartnerType($event->getForm(), $builder->getData()->getPartners());
        // });
        // $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
        //     dump('PRE_SUBMIT');
        //     dump($event);
        //     if (isset($event->getData()['territory'])) {
        //         $this->addBailleurSocialField(
        //             $event->getForm(),
        //             $event->getData()['territory']
        //         );
        //     }
        //     // $this->desactivePartnerType(
        //     //     $event->getForm(),
        //     //     isset($event->getData()['partners']) ? $event->getData()['partners'] : null
        //     // );
        // });

        $builder
            ->add('nom', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'readonly' => isset($insee[$partner->getTerritory()?->getZip()][$partner->getNom()]),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
            ])
            ->add('type', EnumType::class, [
                'class' => EnumPartnerType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'placeholder' => 'Sélectionner un type',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'help' => 'Choisissez un type de partenaire parmi la liste ci-dessous.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'disabled' => !$this->isAdminTerritory,
            ])
            ->add('competence', SearchCheckboxEnumType::class, [
                'class' => Qualification::class,
                'choice_filter' => ChoiceList::filter(
                    $this,
                    function ($choice) {
                        return Qualification::DANGER == $choice ? false : $choice;
                    },
                    'competence'
                ),
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'label' => 'Compétences (facultatif)',
                'noselectionlabel' => 'Sélectionner une ou plusieurs compétences',
                'nochoiceslabel' => 'Aucune compétence disponible',
                'multiple' => true,
                'expanded' => false,
                'help' => 'Choisissez une ou plusieurs compétences parmi la liste ci-dessous.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'required' => false,
            ])
            ->add('isEsaboraActive', CheckboxType::class, [
                'attr' => [
                    'class' => 'fr-toggle__input',
                ],
                'required' => false,
                'disabled' => !$this->isAdminTerritory,
            ])
            ->add('esaboraUrl', UrlType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
                'disabled' => !$this->isAdmin,
                'default_protocol' => null,
            ])
            ->add('esaboraToken', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
                'disabled' => !$this->isAdmin,
            ]);
        if ($this->isAdmin) {
            $builder->add('isIdossActive', CheckboxType::class, [
                'attr' => [
                    'class' => 'fr-toggle__input',
                ],
                'required' => false,
            ])
            ->add('idossUrl', UrlType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
                'default_protocol' => null,
            ]);
        }
        $builder->add('territory', EntityType::class, [
            'class' => Territory::class,
            'query_builder' => function (TerritoryRepository $tr) {
                return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
            },
            'data' => !empty($territory) ? $territory : null,
            'disabled' => !$this->isAdmin,
            'choice_label' => function (Territory $territory) {
                return $territory->getZip().' - '.$territory->getName();
            },
            'attr' => [
                'class' => 'fr-select',
            ],
            'row_attr' => [
                'class' => !$this->isAdmin ? 'fr-input-group fr-hidden' : 'fr-input-group',
            ],
            'label' => 'Territoire',
            'required' => true,
        ]);
        $builder->add('bailleur', EntityType::class, [
            'class' => Bailleur::class,
            'choices' => [], // Initialement vide, rempli dynamiquement par JS
            'choice_label' => 'Bailleur social',
            'placeholder' => $territory ? 'Sélectionner un bailleur social' : 'Sélectionner un territoire pour afficher les bailleurs',
            'required' => false,
            'help' => !$territory ? 'Veuillez sélectionner un territoire pour afficher les bailleurs sociaux.' : null,
            'help_attr' => [
                'class' => !$territory ? 'fr-hint-text fr-hint-text--error' : 'fr-hint-text',
            ],
            'attr' => [
                'data-dynamic' => 'bailleur-social', // Attribut pour cibler le champ avec JS
            ],
        ]);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
        
            // Vérifier si un territoire est sélectionné
            if (isset($data['territory']) && !empty($data['territory'])) {
                // Récupérer le territoire
                $territoryId = $data['territory'];
        
                /** @var Territory $territory */
                $territory = $this->territoryRepository->find($territoryId);
        
                if ($territory) {
                    // Mettre à jour les choix du champ bailleurSocial
                    $this->addBailleurSocialField($form, $territory->getZip());
                }
            }
        });
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $partner = $event->getData();
            $form = $event->getForm();
        
            // Si un territoire est défini, charger les bailleurs sociaux associés
            if ($partner instanceof Partner && $partner->getTerritory()) {
                $this->addBailleurSocialField($form, $partner->getTerritory()->getZip());
            }
        });
        
        
    }
    private function addBailleurSocialField(FormInterface $builder, ?string $territoryZip = null): void
    {
        $builder->add('bailleur', EntityType::class, [
            'class' => Bailleur::class,
            'query_builder' => function (BailleurRepository $bailleurRepository) use ($territoryZip) {
                // Si aucun territoire sélectionné, renvoyer une requête vide
                if (!$territoryZip) {
                    return $bailleurRepository->createQueryBuilder('b')->where('1 = 0');
                }
    
                // Sinon, renvoyer les bailleurs liés au territoire
                return $bailleurRepository->getBailleursByTerritoryQueryBuilder($territoryZip);
            },
            'choice_label' => 'Bailleur social',
            'placeholder' => 'Sélectionner un bailleur social',
            'required' => false,
        ]);
    }
    
    // private function addBailleurSocialField(FormInterface $builder, $territory): void
    // {
    //     dump($territory);
    //     $builder->add('bailleur', EntityType::class, [
    //         'class' => Bailleur::class,
    //         'choices' => [], // Initialement vide, rempli dynamiquement par JS
    //         // 'query_builder' => function (BailleurRepository $bailleurRepository) use ($territory) {
    //         //     if ($territory) {
    //         //         return $bailleurRepository->getBailleursByTerritoryQueryBuilder($territory->getZip());
    //         //     }

    //         //     return $bailleurRepository->createQueryBuilder('b')->where('1 = 0');
    //         // },
    //         'choice_label' => 'Bailleur social',
    //         'placeholder' => $territory ? 'Sélectionner un bailleur social' : 'Sélectionner un territoire pour afficher les bailleurs',
    //         'required' => false,
    //         'help' => !$territory ? 'Veuillez sélectionner un territoire pour afficher les bailleurs sociaux.' : null,
    //         'help_attr' => [
    //             'class' => !$territory ? 'fr-hint-text fr-hint-text--error' : 'fr-hint-text',
    //         ],
    //         'attr' => [
    //             'data-dynamic' => 'bailleur-social', // Attribut pour cibler le champ avec JS
    //         ],
    //         // 'choice_label' => 'nom',
    //         // 'label' => false,
    //         // 'empty_data' => '',
    //         // 'noselectionlabel' => 'Tous les ???',
    //         // 'nochoiceslabel' => !$territory ? 'Sélectionner un territoire pour afficher les bailleurs sociaux disponibles' : 'Aucun bailleur social disponible',
    //     ]);
    // }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'constraints' => [
                new Assert\Callback([$this, 'validatePartnerCanBeNotified']),
                new Assert\Callback([$this, 'validateEmailIsUnique']),
            ],
        ]);
    }

    public function validateEmailIsUnique(mixed $value, ExecutionContextInterface $context)
    {
        if ($value instanceof Partner) {
            $partner = $value;

            if (empty($partner->getEmail())) {
                return;
            }
            /** @var ?User $user */
            $user = $this->userRepository->findOneBy(['email' => $partner->getEmail()]);

            if (!empty($user) && !$user->isUsager()) {
                $context->addViolation('Un utilisateur existe déjà avec cette adresse e-mail.');
            }
        }
    }

    public function validatePartnerCanBeNotified(mixed $value, ExecutionContextInterface $context)
    {
        if ($value instanceof Partner) {
            $partner = $value;
            $usersActive = $partner->getUsers()->filter(function (User $user) {
                return User::STATUS_ACTIVE === $user->getStatut();
            });

            if (!empty($partner->getEmail()) || $usersActive->isEmpty()) {
                return;
            }

            $canBeNotified = $usersActive->exists(function (int $i, User $user) {
                return $user->getIsMailingActive();
            });

            if (!$canBeNotified) {
                $context->addViolation('E-mail générique manquante: Il faut donc obligatoirement qu\'au moins
                1 compte utilisateur accepte de recevoir les e-mails.');
            }
        }
    }
}
