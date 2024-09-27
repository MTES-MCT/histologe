<?php

namespace App\Form;

use App\Dto\SearchUser;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Form\Type\SearchCheckboxType;
use App\Repository\PartnerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchUserType extends AbstractType
{
    private User $user;
    private bool $isAdmin = false;
    private array $roleChoices = [];

    public function __construct(
        private readonly Security $security
    ) {
        /** @var ?User $user */
        $user = $this->security->getUser();
        $this->user = $user;
        $this->roleChoices = User::ROLESV2;
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        } else {
            unset($this->roleChoices['Usager']);
            unset($this->roleChoices['Super Admin']);
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryUser', null, [
            'label' => false,
            'attr' => ['placeholder' => 'Taper le nom ou l\'email d\'un utilisateur'],
        ]);
        if ($this->isAdmin) {
            $builder->add('territory', EntityType::class, [
                'class' => Territory::class,
                'choice_label' => function (Territory $territory) {
                    return $territory->getZip().' - '.$territory->getName();
                },
                'required' => false,
                'placeholder' => 'Tous les territoires',
                'label' => false,
            ]);
        }
        $builder->add('partners', SearchCheckboxType::class, [
            'class' => Partner::class,
            'query_builder' => function (PartnerRepository $partnerRepository) {
                if ($this->isAdmin) {
                    return $partnerRepository->createQueryBuilder('p')
                        ->select('p', 't')
                        ->leftJoin('p.territory', 't')
                        ->orderBy('p.nom', 'ASC');
                }

                return $partnerRepository->createQueryBuilder('p')
                    ->where('p.territory = :territory')
                    ->setParameter('territory', $this->user->getTerritory())
                    ->orderBy('p.nom', 'ASC');
            },
            'choice_label' => function (Partner $partner) {
                if ($this->isAdmin) {
                    return $partner->getNom().'  ('.$partner->getTerritory()?->getZip().')';
                }

                return $partner->getNom();
            },
            'label' => false,
            'noselection' => 'Tous les partenaires',
        ]);
        $builder->add('statut', ChoiceType::class, [
            'choices' => [
                'Activé' => 1,
                'Non activé' => 0,
            ],
            'required' => false,
            'placeholder' => 'Statut',
            'label' => false,
        ]);
        $builder->add('role', ChoiceType::class, [
            'choices' => $this->roleChoices,
            'required' => false,
            'placeholder' => 'Rôle',
            'label' => false,
        ]);
        $builder->add('page', HiddenType::class);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Rechercher',
            'attr' => ['class' => 'btn btn-primary'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchUser::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-user-form', 'class' => 'fr-p-4v'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
