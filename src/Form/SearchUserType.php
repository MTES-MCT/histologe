<?php

namespace App\Form;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Form\Type\SearchCheckboxType;
use App\Form\Type\TerritoryChoiceType;
use App\Repository\PartnerRepository;
use App\Service\ListFilters\SearchUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchUserType extends AbstractType
{
    /** @var array<string, string> */
    private const array PERMISSION_CHOICES = [
        'Oui' => 'Oui',
        'Non' => 'Non',
    ];
    /** @var array<string, string> */
    private const array EMAIL_DELIVERY_ISSUE = [
        'Adresse e-mail fonctionnelle' => 'Non',
        'Problème d\'adresse e-mail' => 'Oui',
    ];

    private bool $isAdmin = false;
    /**
     * @var array<string, string>
     */
    private array $roleChoices = [];

    public function __construct(
        private readonly Security $security,
    ) {
        $this->roleChoices = User::ROLES;
        unset($this->roleChoices['Usager']);
        unset($this->roleChoices['API']);
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        } else {
            unset($this->roleChoices['Super Admin']);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $showAllFields = $options['show_all_fields'];

        $builder->add('queryUser', SearchType::class, [
            'required' => false,
            'label' => 'Utilisateur',
            'attr' => ['placeholder' => $showAllFields ? 'Taper le nom ou l\'e-mail d\'un utilisateur' : 'Taper l\'e-mail d\'un utilisateur'],
        ]);

        $builder->add('statut', ChoiceType::class, [
            'choices' => [
                'Activé' => UserStatus::ACTIVE->value,
                'Non activé' => UserStatus::INACTIVE->value,
            ],
            'required' => false,
            'placeholder' => 'Tous les statuts',
            'label' => 'Statut',
        ]);

        $builder->add('page', HiddenType::class);

        if (!$showAllFields) {
            return;
        }
        if ($this->isAdmin) {
            $builder->add('territory', TerritoryChoiceType::class);
        }
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder) {
            $this->addPartnersField(
                $event->getForm(),
                $builder->getData()->getTerritory(),
                $builder->getData()->getPartnerType()
            );
            $this->desactivePartnerType($event->getForm(), $builder->getData()->getPartners());
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            if ($this->isAdmin && isset($event->getData()['territory'])) {
                $this->addPartnersField(
                    $event->getForm(),
                    $event->getData()['territory'],
                    isset($event->getData()['partnerType']) ? $event->getData()['partnerType'] : null
                );
            }
            $this->desactivePartnerType(
                $event->getForm(),
                isset($event->getData()['partners']) ? $event->getData()['partners'] : null
            );
        });
        $builder->add('role', ChoiceType::class, [
            'choices' => $this->roleChoices,
            'required' => false,
            'placeholder' => 'Tous les rôles',
            'label' => 'Rôle',
        ]);
        $builder->add('permissionAffectation', ChoiceType::class, [
            'choices' => self::PERMISSION_CHOICES,
            'required' => false,
            'placeholder' => 'Tous les droits d\'affectation',
            'label' => 'Droits d\'affectation',
        ]);
        $builder->add('emailDeliveryIssue', ChoiceType::class, [
            'choices' => self::EMAIL_DELIVERY_ISSUE,
            'required' => false,
            'placeholder' => 'Tous',
            'label' => 'Problème d\'adresse e-mail',
        ]);

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A -> Z)' => 'u.nom-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 'u.nom-DESC',
                'Partenaire (A -> Z)' => 'p.nom-ASC',
                'Partenaire inversé (Z -> A)' => 'p.nom-DESC',
                'Connexion la plus récente' => 'u.lastLoginAt-DESC',
                'Connexion la plus ancienne' => 'u.lastLoginAt-ASC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'u.nom-ASC',
        ]);
    }

    private function addPartnersField(FormInterface $builder, string|Territory|null $territory, mixed $partnerType): void
    {
        $builder->add('partners', SearchCheckboxType::class, [
            'class' => Partner::class,
            'query_builder' => function (PartnerRepository $partnerRepository) use ($territory, $partnerType) {
                $query = $partnerRepository->createQueryBuilder('p')
                    ->where('p.territory = :territory')
                    ->setParameter('territory', $territory);
                if (null !== $partnerType && '' !== $partnerType) {
                    $query->andWhere('p.type = :partnerType')
                    ->setParameter('partnerType', $partnerType);
                }
                $query->orderBy('p.nom', 'ASC');

                return $query;
            },
            'choice_label' => 'nom',
            'label' => 'Partenaire',
            'noselectionlabel' => 'Tous les partenaires',
            'required' => false,
            'nochoiceslabel' => !$territory ? 'Sélectionner un territoire pour afficher les partenaires disponibles' : 'Aucun partenaire disponible',
        ]);
    }

    private function desactivePartnerType(FormInterface $builder, mixed $partners): void
    {
        $options = [
            'class' => PartnerType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'placeholder' => 'Tous les types de partenaire',
            'required' => false,
            'disabled' => isset($partners) && !empty($partners) && \count($partners) > 0,
            'label' => 'Type de partenaire',
        ];

        $builder->add('partnerType', EnumType::class, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchUser::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-user-form', 'class' => 'fr-p-4v bo-filter-form'],
            'show_all_fields' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
