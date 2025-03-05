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
        /** @var Partner $partner */
        $partner = $builder->getData();
        $territory = $options['data']->getTerritory();

        $builder
            ->add('nom', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
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
        $this->addBailleurSocialField($builder, $territory?->getZip(), $partner);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, fn (FormEvent $event) => $this->handleTerritoryChange($event));
        $builder->addEventListener(FormEvents::PRE_SET_DATA, fn (FormEvent $event) => $this->handleTerritoryChange($event, true));
    }

    private function handleTerritoryChange(FormEvent $event, bool $isPreSetData = false): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        $territory = $isPreSetData && $data instanceof Partner
            ? $data->getTerritory()
            : (\array_key_exists('territory', $data) ? $this->territoryRepository->find($data['territory']) : null);

        $this->addBailleurSocialField($form, $territory?->getZip(), $data);
    }

    private function addBailleurSocialField(FormBuilderInterface|FormInterface $builder, ?string $territoryZip = null, $data = null): void
    {
        if (null === $territoryZip) {
            if ($data instanceof Partner && null !== $data->getTerritory()) {
                $territoryZip = $data->getTerritory()->getZip();
            } elseif ($this->isAdmin) {
                $territoryZip = '01';
            } else {
                /** @var User $user */
                $user = $this->security->getUser();
                $territoryZip = $user->getFirstTerritory()->getZip();
            }
        }
        $builder->add('bailleur', EntityType::class, [
            'class' => Bailleur::class,
            'query_builder' => fn (BailleurRepository $bailleurRepository) => $bailleurRepository->getBailleursByTerritoryQueryBuilder($territoryZip),
            'choice_label' => 'name',
            'placeholder' => 'Sélectionner une dénomination officielle pour le bailleur social',
            'required' => false,
            'attr' => ['data-dynamic' => 'bailleur-social'],
        ]);
    }

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
            if (!$value->receiveEmailNotifications()) {
                $context->addViolation('E-mail générique manquant: Il faut obligatoirement qu\'un compte utilisateur accepte de recevoir les e-mails.');
            }
        }
    }
}
