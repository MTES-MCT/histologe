<?php

namespace App\Dto\Api\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    description: 'Payload pour mettre à jour une affectation.',
)]
class AffectationRequest implements RequestInterface
{
    public function __construct(
        #[OA\Property(
            description: 'Statut de l\'affectation. <br>
            Les transitions possibles entre statuts sont soumises à des contraintes spécifiques :<br>
            <ul>
              <li>`NOUVEAU` : peut évoluer vers `EN_COURS` ou `REFUSE`</li>
              <li>`EN_COURS` : peut évoluer vers `FERME`</li>
              <li>`REFUSE` : peut évoluer vers `EN_COURS`</li>
              <li>`FERME` : peut revenir à `NOUVEAU`</li>
            </ul>',
            enum: ['NOUVEAU', 'EN_COURS', 'FERME', 'REFUSE'],
            example: 'EN_COURS',
        )]
        #[Assert\Choice(
            choices: ['NOUVEAU', 'EN_COURS', 'FERME', 'REFUSE'],
            message: 'Cette valeur doit être l\'un des choix suivants : {{ choices }}')
        ]
        public ?string $statut = null,
        #[OA\Property(
            description: 'Le motif de cloture de l\'affectation, celui doit être accompagné d\'un message',
            enum: [
                'ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE',
                'DEPART_OCCUPANT',
                'INSALUBRITE',
                'LOGEMENT_DECENT',
                'NON_DECENCE',
                'PERIL',
                'REFUS_DE_VISITE',
                'REFUS_DE_TRAVAUX',
                'RELOGEMENT_OCCUPANT',
                'RESPONSABILITE_DE_L_OCCUPANT',
                'RSD',
                'TRAVAUX_FAITS_OU_EN_COURS',
                'DOUBLON',
                'AUTRE',
            ],
            example: 'DEPART_OCCUPANT',
        )]
        #[Assert\Choice(
            choices: [
                'ABANDON_DE_PROCEDURE_ABSENCE_DE_REPONSE',
                'DEPART_OCCUPANT',
                'INSALUBRITE',
                'LOGEMENT_DECENT',
                'NON_DECENCE',
                'PERIL',
                'REFUS_DE_VISITE',
                'REFUS_DE_TRAVAUX',
                'RELOGEMENT_OCCUPANT',
                'RESPONSABILITE_DE_L_OCCUPANT',
                'RSD',
                'TRAVAUX_FAITS_OU_EN_COURS',
                'DOUBLON',
                'AUTRE',
            ],
            message: 'Cette valeur doit être l\'un des choix suivants : {{ choices }}'
        )]
        #[Assert\When(
            expression: 'this.statut === "FERME"',
            constraints: [
                new Assert\NotNull(message: 'Le motifCloture est obligatoire lorsque statut est FERME.'),
            ]
        )]
        public ?string $motifCloture = null,
        #[OA\Property(
            description: 'Le motif de refus de l\'affectation, celui doit être accompagné d\'un message',
            enum: [
                'HORS_PDLHI',
                'HORS_ZONE_GEOGRAPHIQUE',
                'HORS_COMPETENCE',
                'DOUBLON',
                'AUTRE',
            ],
            example: 'EN_COURS',
        )]
        #[Assert\Choice(
            choices: [
                'HORS_PDLHI',
                'HORS_ZONE_GEOGRAPHIQUE',
                'HORS_COMPETENCE',
                'DOUBLON',
                'AUTRE',
            ],
            message: 'Cette valeur doit être l\'un des choix suivants : {{ choices }}'
        )]
        #[Assert\When(
            expression: 'this.statut === "REFUSE"',
            constraints: [
                new Assert\NotNull(message: 'Le motifRefus est obligatoire lorsque statut est REFUSE.'),
            ]
        )]
        public ?string $motifRefus = null,

        #[OA\Property(
            description: 'Un message est obligatoire lorsque statut est REFUSE ou FERME.',
            example: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        )]
        #[Assert\When(
            expression: 'this.statut === "REFUSE" || this.statut === "FERME"',
            constraints: [
                new Assert\NotNull(message: 'Le message est obligatoire lorsque statut est REFUSE ou FERME.'),
            ]
        )]
        #[Assert\Length(min: 10)]
        public ?string $message = null,

        #[OA\Property(
            description: 'Il est obligatoire d\'indiquer si l\'usager doit être notifié lors d\'une réouverture (TRANSITION : FERME → NOUVEAU).',
            example: 'true',
        )]
        public ?bool $notifyUsager = null,
    ) {
    }
}
