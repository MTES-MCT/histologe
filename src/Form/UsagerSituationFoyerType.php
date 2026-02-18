<?php

namespace App\Form;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UsagerSituationFoyerType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isLogementSocial', ChoiceType::class, [
                'label' => 'Est-ce qu\'il s\'agit d\'un logement social ?',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                    'Ne sais pas' => 'nsp',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez déterminer s\'il s\'agit d\'un logement social.',
                    ),
                ],
            ])
            ->add('isRelogement', ChoiceType::class, [
                'label' => 'Est-ce qu\'une demande de relogement a été faite ?',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotBlank(
                        groups: ['LOCATAIRE', 'BAILLEUR_OCCUPANT'],
                        message: 'Veuillez préciser si une demande de relogement a été faite.',
                    ),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => [
                    'class' => 'fr-btn--primary',
                ],
            ]);

        /*


					{% if signalement.isAllocataire in [null, ''] %}
					{% elseif signalement.isAllocataire in ['oui', '1'] %}
						<li>Allocataire : Oui</li>
					{% elseif signalement.isAllocataire in ['non', '0'] %}
						<li>Allocataire : Non</li>
					{% elseif signalement.isAllocataire %}
						<li>Allocataire : {{ signalement.isAllocataire }}</li>
					{% endif %}
					{% if (signalement.dateNaissanceOccupant) %}
						<li>Date de naissance : {{ signalement.dateNaissanceOccupant.format('d/m/Y') }}</li>
					{% elseif signalement.naissanceOccupants %}
						<li>Date de naissance : {{ signalement.naissanceOccupants}}</li>
					{% endif %}
					{% if signalement.numAllocataire %}
						<li>N° allocataire : {{ signalement.numAllocataire }}</li>
					{% endif %}
					{% if signalement.situationFoyer %}
						{% if signalement.situationFoyer.logementSocialMontantAllocation %}
							<li>Montant allocation :
								{{ signalement.situationFoyer.logementSocialMontantAllocation }} €
							</li>
						{% endif %}
						{% if signalement.situationFoyer.travailleurSocialQuitteLogement(false) %}
							<li>Souhaite quitter le logement :
								{{signalement.situationFoyer.travailleurSocialQuitteLogement(false)|capitalize}}
							</li>
						{% endif %}
						{% if signalement.situationFoyer.travailleurSocialPreavisDepart(false) %}
							<li>Préavis de départ :
								{{signalement.situationFoyer.travailleurSocialPreavisDepart(false)|capitalize}}
							</li>
						{% endif %}
						{% if signalement.situationFoyer.travailleurSocialAccompagnement(false) %}
							<li>Accompagnement par un ou une travailleuse sociale :
								{{signalement.situationFoyer.travailleurSocialAccompagnement(false)|capitalize}}
							</li>
						{% endif %}
						{% if signalement.situationFoyer.travailleurSocialAccompagnement(false) and signalement.situationFoyer.travailleurSocialAccompagnementNomStructure %}
							<li>Nom de la structure d'accompagnement :
								{{ signalement.situationFoyer.travailleurSocialAccompagnementNomStructure }}
							</li>
						{% endif %}
					{% endif %}
					{% if signalement.informationComplementaire %}
						{% if signalement.informationComplementaire.informationsComplementairesSituationOccupantsBeneficiaireRsa %}
							<li>Bénéficiaire RSA :
								{{signalement.informationComplementaire.informationsComplementairesSituationOccupantsBeneficiaireRsa|capitalize}}
							</li>
						{% endif %}
						{% if signalement.informationComplementaire.informationsComplementairesSituationOccupantsBeneficiaireFsl %}
							<li>Bénéficiaire FSL :
								{{signalement.informationComplementaire.informationsComplementairesSituationOccupantsBeneficiaireFsl|capitalize}}
							</li>
						{% endif %}
						{% if signalement.informationComplementaire.informationsComplementairesSituationOccupantsRevenuFiscal %}
							<li>Revenu fiscal de référence :
								{{signalement.informationComplementaire.informationsComplementairesSituationOccupantsRevenuFiscal}} €
							</li>
						{% endif %}
					{% endif %}
					{% if signalement.informationProcedure %}
						<li>
							Si des travaux sont faits, voulez-vous rester dans le logement ? 
							{{signalement.informationProcedure.infoProcedureDepartApresTravaux(false)|capitalize}}
						</li>
					{% endif %}


        $builder
            ->add('denominationAgence', null, [
                'label' => 'Dénomination',
                'required' => false,
            ])


            */
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
        ]);
    }
}
