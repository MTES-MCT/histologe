<?php

namespace App\Form;

use App\Entity\Enum\PartnerType;
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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
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
        $builder->add('reference', SearchType::class, [
            'required' => false,
            'label' => 'Référence du signalement',
            'attr' => ['placeholder' => 'Taper la référence'],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $territory = $data->getTerritory() ? $this->territoryRepository->find($data->getTerritory()) : null;
            $partnerType = $data->getPartnerType();
            $this->addPartnersField(
                $event->getForm(),
                $territory,
                $partnerType
            );
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $territory = null;
            if (isset($data['territory'])) {
                $territory = $this->territoryRepository->find($data['territory']);
            }
            $partnerType = null;
            if (isset($data['partnerType'])) {
                $partnerType = PartnerType::tryFrom($data['partnerType']);
            }

            $this->addPartnersField(
                $event->getForm(),
                $territory,
                $partnerType
            );

            if (isset($data['page']) && (!is_numeric($data['page']))) {
                $data['page'] = 1;
            }

            if (isset($data['service']) && isset($data['action'])) {
                $data['action'] = $data['service'].' - '.$data['action'];
                unset($data['service']);
            }
            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) {
            /** @var SearchInterconnexion $searchInterconnexion */
            $searchInterconnexion = $event->getData();
            $actionValue = $searchInterconnexion->getAction();

            if ($actionValue && str_contains($actionValue, ' - ')) {
                [$service, $action] = explode(' - ', $actionValue);
                $searchInterconnexion
                    ->setService($service)
                    ->setAction($action);
            }
        });

        $builder->add('status', ChoiceType::class, [
            'required' => false,
            'label' => 'Statut',
            'placeholder' => 'Tous les statuts',
            'choices' => [
                'Success' => 'success',
                'Warning' => 'warning',
                'Fail' => 'failed',
            ],
        ]);
        $builder->add('action', ChoiceType::class, [
            'required' => false,
            'label' => 'Evénement',
            'placeholder' => 'Tous les événements',
            'choices' => [
                AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_PUSH_DOSSIER => AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_PUSH_DOSSIER,
                AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE => AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE,
                AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE => AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE,
                AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_SYNC_DOSSIER => AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_SYNC_DOSSIER,
                AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE => AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_SYNC_DOSSIER_ARRETE,
                AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE => AbstractEsaboraService::TYPE_SERVICE.' - '.AbstractEsaboraService::ACTION_SYNC_DOSSIER_VISITE,
                AbstractEsaboraService::TYPE_SERVICE.' - '.EsaboraSCHSService::ACTION_SYNC_EVENTS => AbstractEsaboraService::TYPE_SERVICE.' - '.EsaboraSCHSService::ACTION_SYNC_EVENTS,
                AbstractEsaboraService::TYPE_SERVICE.' - '.EsaboraSCHSService::ACTION_SYNC_EVENTFILES => AbstractEsaboraService::TYPE_SERVICE.' - '.EsaboraSCHSService::ACTION_SYNC_EVENTFILES,
                IdossService::TYPE_SERVICE.' - '.IdossService::ACTION_PUSH_DOSSIER => IdossService::TYPE_SERVICE.' - '.IdossService::ACTION_PUSH_DOSSIER,
                IdossService::TYPE_SERVICE.' - '.IdossService::ACTION_LIST_STATUTS => IdossService::TYPE_SERVICE.' - '.IdossService::ACTION_LIST_STATUTS,
                IdossService::TYPE_SERVICE.' - '.IdossService::ACTION_UPLOAD_FILES => IdossService::TYPE_SERVICE.' - '.IdossService::ACTION_UPLOAD_FILES,
            ],
        ]);
        $builder->add('partnerType', EnumType::class, [
            'required' => false,
            'class' => PartnerType::class,
            'label' => 'Type de partenaire',
            'placeholder' => 'Type de partenaire',
            'choices' => PartnerType::getInterconnected(),
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
        $builder->add('showOnlyDataErrors', CheckboxType::class, [
            'required' => false,
            'label' => 'Masquer les erreurs techniques',
            'attr' => [
                'class' => 'fr-toggle__input fr-auto-submit',
            ],
            'row_attr' => [
                'class' => 'fr-toggle',
            ],
            'label_attr' => [
                'class' => 'fr-toggle__label',
            ],
            'false_values' => ['0', null],
            'empty_data' => '0',
        ]);
        $builder->add('page', HiddenType::class);
    }

    /**
     * @param FormInterface<mixed> $builder
     */
    private function addPartnersField(FormInterface $builder, ?Territory $territory, ?PartnerType $partnerType): void
    {
        $choicesPartners = [];
        /** @var User $user */
        $user = $this->security->getUser();
        $searchPartner = new SearchPartner($user);
        $searchPartner->setIsOnlyInterconnected(true);
        if ($territory) {
            $searchPartner->setTerritoire($territory);
        }
        if ($partnerType) {
            $searchPartner->setPartnerType($partnerType);
        }
        $partners = $this->partnerRepository->getPartners(1000, $searchPartner);

        /** @var array{0: Partner, isNotifiable: int} $partner */
        foreach ($partners as $partner) {
            $choicesPartners[] = $partner[0];
        }

        $builder->add('partner', EntityType::class, [
            'class' => Partner::class,
            'choices' => $choicesPartners,
            'choice_label' => static function (Partner $partner) {
                return sprintf('%s (%s)', $partner->getNom(), str_pad($partner->getTerritory()->getZip(), 2, '0'));
            },
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
            'attr' => ['id' => 'search-interconnexion-form', 'class' => 'fr-pt-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
