<?php

namespace App\Form;

use App\Entity\Commune;
use App\Entity\Enum\PartnerType as EnumPartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\Zone;
use App\Form\Type\SearchCheckboxEnumType;
use App\Form\Type\SearchCheckboxType;
use App\Manager\CommuneManager;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
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
        private readonly CommuneManager $communeManager,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
        #[Autowire(env: 'FEATURE_ZONAGE')]
        private readonly bool $featureZonage,
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
        if ($options['territory']) {
            $territory = $options['territory'];
        } else {
            $territory = $options['data']->getTerritory();
        }

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
            ->add('insee', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'readonly' => isset($insee[$partner->getTerritory()?->getZip()][$partner->getNom()]),
                ],
                'required' => false,
            ])
            ->add('zones_pdl', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'readonly' => isset($insee[$partner->getTerritory()?->getZip()][$partner->getNom()]),
                ],
                'required' => false,
                'mapped' => false,
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
            ]);
        }
        $builder->add('territory', EntityType::class, [
            'class' => Territory::class,
            'query_builder' => function (TerritoryRepository $tr) {
                return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
            },
            'data' => !empty($territory) ? $territory : null,
            'disabled' => !$options['can_edit_territory'],
            'choice_label' => function (Territory $territory) {
                return $territory->getZip().' - '.$territory->getName();
            },
            'attr' => [
                'class' => 'fr-select',
            ],
            'row_attr' => [
                'class' => !$options['can_edit_territory'] ? 'fr-input-group fr-hidden' : 'fr-input-group',
            ],
            'label' => 'Territoire',
            'required' => true,
        ]);
        if ($this->featureZonage && $partner->getTerritory()) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder) {
                $this->addZonesField($event->getForm(), $builder->getData()->getTerritory());
            });
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                if (isset($event->getData()['territory'])) {
                    $this->addZonesField($event->getForm(), $event->getData()['territory']);
                }
            });
        }
        $builder->get('insee')->addModelTransformer(new CallbackTransformer(
            function ($tagsAsArray) {
                // transform the array to a string
                return implode(',', $tagsAsArray);
            },
            function ($tagsAsString) {
                // transform the string back to an array
                $pattern = '/(\s*,*\s*)*,+(\s*,*\s*)*/';

                return null !== $tagsAsString ? preg_split($pattern, $tagsAsString, -1, \PREG_SPLIT_NO_EMPTY) : [];
            }
        ));

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Partner $partner */
            $partner = $event->getData();
            $form = $event->getForm();

            if ($form->get('zones_pdl')->getData()) {
                if (null === $partner->getTerritory()) {
                    $options = $form->getConfig()->getOptions();
                    if ($options['territory']) {
                        $territory = $options['territory'];
                    } else {
                        $territory = $options['data']->getTerritory();
                    }
                } else {
                    $territory = $partner->getTerritory();
                }

                $zonesPdlList = explode(',', $form->get('zones_pdl')->getData());
                foreach ($zonesPdlList as $zonePdl) {
                    $communes = $this->communeManager->findBy(['codeInsee' => trim($zonePdl)]);
                    if (!\count($communes)) {
                        $form->get('zones_pdl')->addError(new FormError('Il n\'existe pas de commune avec le code insee '.trim($zonePdl)));
                    }
                    foreach ($communes as $commune) {
                        if ($commune->getTerritory() === $territory) {
                            $commune->setIsZonePermisLouer(true);
                        } elseif ($commune->getTerritory() !== $territory) {
                            $form->get('zones_pdl')->addError(new FormError('La commune avec le code insee '.trim($zonePdl).' ne fait pas partie du territoire du partenaire'));
                        }
                    }
                }
            }
        });
    }

    private function addZonesField(FormInterface $builder, $territory): void
    {
        $builder->add('zones', SearchCheckboxType::class, [
            'class' => Zone::class,
            'query_builder' => function (ZoneRepository $zoneRepository) use ($territory) {
                return $zoneRepository->createQueryBuilder('z')
                    ->where('z.territory = :territory')
                    ->setParameter('territory', $territory)
                    ->orderBy('z.name', 'ASC');
            },
            'choice_label' => 'name',
            'label' => 'Zones',
            'noselectionlabel' => 'Sélectionnez les zones',
            'nochoiceslabel' => 'Aucune zone disponible',
            'by_reference' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'allow_extra_fields' => true,
            'territory' => null,
            'route' => null,
            'can_edit_territory' => true,
            'constraints' => [
                new Assert\Callback([$this, 'validatePartnerCanBeNotified']),
                new Assert\Callback([$this, 'validateInseeInTerritory']),
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

    public function validateInseeInTerritory(mixed $value, ExecutionContextInterface $context)
    {
        if ($value instanceof Partner) {
            $partner = $value;
            $codesInsee = $partner->getInsee();
            if (empty($codesInsee)) {
                return;
            }
            if (null === $partner->getTerritory()) {
                $options = $context->getRoot()->getConfig()->getOptions();
                if ($options['territory']) {
                    $territory = $options['territory'];
                } else {
                    $territory = $options['data']->getTerritory();
                }
            } else {
                $territory = $partner->getTerritory();
            }

            foreach ($codesInsee as $insee) {
                /** @var ?Commune $commune */
                $commune = $this->communeManager->findOneBy(['codeInsee' => trim($insee)]);
                if (null === $commune) {
                    $context->addViolation('Il n\'existe pas de commune avec le code insee '.$insee);
                } elseif ($commune->getTerritory() !== $territory) {
                    $context->addViolation('La commune avec le code insee '.$insee.' ne fait pas partie du territoire du partenaire');
                }
            }
        }
    }
}
