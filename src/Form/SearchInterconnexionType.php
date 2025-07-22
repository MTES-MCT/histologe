<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Form\Type\TerritoryChoiceType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraSCHSService;
use App\Service\Interconnection\Idoss\IdossService;
use App\Service\ListFilters\SearchInterconnexion;
use App\Service\ListFilters\SearchPartner;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchInterconnexionType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territory', TerritoryChoiceType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder) {
            $territory = $builder->getData()->getTerritory() ? $this->territoryRepository->find($builder->getData()->getTerritory()) : null;
            $this->addPartnersField(
                $event->getForm(),
                $territory
            );
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            if (isset($event->getData()['territory'])) {
                $territory = $event->getData()['territory'] ? $this->territoryRepository->find($event->getData()['territory']) : null;
                $this->addPartnersField(
                    $event->getForm(),
                    $territory
                );
            }
            $data = $event->getData();
            if (isset($data['page']) && (!is_numeric($data['page']))) {
                $data['page'] = 1;
            }
            $event->setData($data);
        });
        $builder->add('status', ChoiceType::class, [
            'required' => false,
            'label' => 'Statut',
            'placeholder' => 'Tous les statuts',
            'choices' => [
                'Success' => 'success',
                'Fail' => 'failed',
            ],
        ]);
        $builder->add('action', ChoiceType::class, [
            'required' => false,
            'label' => 'Action',
            'placeholder' => 'Toutes les actions',
            'choices' => [
                AbstractEsaboraService::ACTION_PUSH_DOSSIER => AbstractEsaboraService::ACTION_PUSH_DOSSIER,
                AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE => AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE,
                AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE => AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE,
                AbstractEsaboraService::ACTION_SYNC_DOSSIER => AbstractEsaboraService::ACTION_SYNC_DOSSIER,
                AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE => AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE,
                AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE => AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE,
                EsaboraSCHSService::ACTION_SYNC_EVENTS => EsaboraSCHSService::ACTION_SYNC_EVENTS,
                EsaboraSCHSService::ACTION_SYNC_EVENTFILES => EsaboraSCHSService::ACTION_SYNC_EVENTFILES,    
                IdossService::ACTION_LIST_STATUTS => IdossService::ACTION_LIST_STATUTS,
                IdossService::ACTION_UPLOAD_FILES => IdossService::ACTION_UPLOAD_FILES,
            ],
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Synchro la plus récente' => 'j.createdAt-DESC',
                'Synchro la plus ancienne' => 'j.createdAt-ASC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'j.createdAt-DESC',
        ]);
        $builder->add('page', HiddenType::class);
    }

    private function addPartnersField(FormInterface $builder, ?Territory $territory): void
    {
        $choicesPartners = [];
        /** @var User $user */
        $user = $this->security->getUser();
        $searchPartner = new SearchPartner($user);
        $searchPartner->setIsOnlyInterconnected(true);
        if ($territory) {
            $searchPartner->setTerritoire($territory);
        }
        $partners = $this->partnerRepository->getPartners(1000, $searchPartner);

        foreach ($partners as $partner) {
            $choicesPartners[] = $partner[0];
        }

        $builder->add('partner', EntityType::class, [
            'class' => Partner::class,
            'choices' => $choicesPartners,
            'choice_label' => 'nom',
            'required' => false,
            'label' => 'Partenaire',
            'placeholder' => 'Tous les partenaires',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchInterconnexion::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-interconnexion-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
