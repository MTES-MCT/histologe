<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230407151135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Affect type to partner';
    }

    private function setType(PartnerType $type, bool $isExact, string $name)
    {
        if ($isExact) {
            $this->addSql('UPDATE partner SET type = "'.$type->name.'" WHERE nom="'.$name.'"');
        } else {
            $this->addSql('UPDATE partner SET type = "'.$type->name.'" WHERE lower(nom) LIKE "%'.$name.'%"');
        }
    }

    private function setCommunes()
    {
        // TODO : est-ce qu'on peut se contenter de ça, où on est obligés d'ajouter 1197 lignes pour les communes
        $this->addSql('UPDATE partner SET type = "'.PartnerType::COMMUNE_SCHS->name.'" WHERE is_commune=1');
    }

    private function setAdil()
    {
        $this->setType(PartnerType::ADIL, false, 'adil');
        $this->setType(PartnerType::ADIL, true, 'A.D.I.L.');
        $this->setType(PartnerType::ADIL, true, 'ADMIL');
    }

    private function setARS()
    {
        $this->setType(PartnerType::ARS, false, 'ars');
        $this->setType(PartnerType::ARS, true, 'A.R.S.');
        $this->setType(PartnerType::ARS, true, 'Dt-arS 54');
    }

    private function setCAF()
    {
        $this->setType(PartnerType::CAF_MSA, false, 'caf');
        $this->setType(PartnerType::CAF_MSA, false, 'msa');
    }

    private function setCCAS()
    {
        $this->setType(PartnerType::CCAS, false, 'ccas');
        $this->setType(PartnerType::CCAS, true, 'CIAS CAPA');
    }

    private function setDDETS()
    {
        $this->setType(PartnerType::DDETS, false, 'ddets');
        $this->setType(PartnerType::DDETS, true, 'CDC Blaye');
        $this->setType(PartnerType::DDETS, true, 'CDC Estuaire');
        $this->setType(PartnerType::DDETS, true, 'CDC Latitude Nord Gironde');
        $this->setType(PartnerType::DDETS, true, 'Grand Cubzaguais CDC');
    }

    private function setDDTM()
    {
        $this->setType(PartnerType::DDT_M, false, 'ddt');
        $this->setType(PartnerType::DDT_M, true, 'Commission de qualification du PDLHIml 33');
        $this->setType(PartnerType::DDT_M, true, 'DEAL');
    }

    private function setOperateurs()
    {
        $this->setType(PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, false, 'urbanis');
        $this->setType(PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, false, 'soliha');
        $this->setType(PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, false, 'opérateur');
        $this->setType(PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, true, 'POLYGONE');

        $competenceVisite = [Qualification::VISITES];
        $this->addSql('UPDATE partner SET competence = \''.json_encode($competenceVisite).'\' WHERE lower(nom) LIKE "%urbanis%"');
        $this->addSql('UPDATE partner SET competence = \''.json_encode($competenceVisite).'\' WHERE lower(nom) LIKE "%soliha%"');
        $this->addSql('UPDATE partner SET competence = \''.json_encode($competenceVisite).'\' WHERE lower(nom) LIKE "%opérateur%"');
        $this->addSql('UPDATE partner SET competence = \''.json_encode($competenceVisite).'\' WHERE nom="POLYGONE"');
    }

    private function setPolice()
    {
        $this->setType(PartnerType::POLICE_GENDARMERIE, false, 'police');
        $this->setType(PartnerType::POLICE_GENDARMERIE, false, 'gendarmerie');
        $this->setType(PartnerType::POLICE_GENDARMERIE, true, 'PM de Ligueil');
    }

    private function setPrefecture()
    {
        $this->setType(PartnerType::PREFECTURE, false, 'prefecture');
        $this->setType(PartnerType::PREFECTURE, false, 'préfecture');
        $this->setType(PartnerType::PREFECTURE, true, 'Sous-Pref Dreux');
    }

    private function setTribunal()
    {
        $this->setType(PartnerType::TRIBUNAL, false, 'tribunal');
        $this->setType(PartnerType::TRIBUNAL, false, 'parquet');
    }

    private function setAssociation()
    {
        $this->setType(PartnerType::ASSOCIATION, true, 'Association Espoir 54 - EPSIL');
        $this->setType(PartnerType::ASSOCIATION, true, 'FAMILLE & PROVENCE');
        $this->setType(PartnerType::ASSOCIATION, true, 'ADIS');
        $this->setType(PartnerType::ASSOCIATION, true, 'ADOMA');
        $this->setType(PartnerType::ASSOCIATION, true, 'APPORT SANTE');
        $this->setType(PartnerType::ASSOCIATION, true, 'ALPIL DMLHI');
        $this->setType(PartnerType::ASSOCIATION, true, 'ALPIL INCURIE');
        $this->setType(PartnerType::ASSOCIATION, true, 'Bertrand MEAR - Directeur - Heol');
        $this->setType(PartnerType::ASSOCIATION, true, 'Cathy PENNEC - Educatrice Spécialisée ASLL - La Croix rouge');
        $this->setType(PartnerType::ASSOCIATION, true, 'Christelle MERDY - Assocation ASAD');
        $this->setType(PartnerType::ASSOCIATION, true, 'CLIC COOMAID');
        $this->setType(PartnerType::ASSOCIATION, true, 'Fondation Abbé Pierre');
        $this->setType(PartnerType::ASSOCIATION, true, 'France Victimes Siavic');
        $this->setType(PartnerType::ASSOCIATION, true, 'Interfaces');
        $this->setType(PartnerType::ASSOCIATION, true, 'Lucie KERAUDREN - Educatrice - Association Croix Rouge - ASLL');
        $this->setType(PartnerType::ASSOCIATION, true, 'Marjolaine BOLZER - Mission Locale pays de Conouaille');
        $this->setType(PartnerType::ASSOCIATION, true, 'Thumette RIOUAL - Educatrice - Association Croix-Rouge - ASLL');
        $this->setType(PartnerType::ASSOCIATION, true, 'UDAF 08');
        $this->setType(PartnerType::ASSOCIATION, true, 'Udaf');
        $this->setType(PartnerType::ASSOCIATION, true, 'Valérie BOULC\'H - CLCV');
    }

    private function setAutre()
    {
        $this->setType(PartnerType::AUTRE, true, 'Administrateurs Histologe ALL');
        $this->setType(PartnerType::AUTRE, true, 'Altair');
        $this->setType(PartnerType::AUTRE, true, 'Compagnons Bâtisseurs de Provence');
        $this->setType(PartnerType::AUTRE, true, 'CMS de SISTERON');
        $this->setType(PartnerType::AUTRE, true, 'SOLIHA-DALO');
        $this->setType(PartnerType::AUTRE, true, 'ANCB');
        $this->setType(PartnerType::AUTRE, true, 'ANCOLS');
        $this->setType(PartnerType::AUTRE, true, 'ATEL - SERVICES SOCIAUX');
        $this->setType(PartnerType::AUTRE, true, 'Claire LABEL - ALECOB');
        $this->setType(PartnerType::AUTRE, true, 'Clic Val de Durance');
        $this->setType(PartnerType::AUTRE, true, 'Collectif SIAO48');
        $this->setType(PartnerType::AUTRE, true, 'Compagnons Bâtiseurs de Bretagne');
        $this->setType(PartnerType::AUTRE, true, 'Compagnons batisseurs');
        $this->setType(PartnerType::AUTRE, true, 'DDCS du Nord');
        $this->setType(PartnerType::AUTRE, true, 'FONDS SOLIDARITE LOGEMENT');
        $this->setType(PartnerType::AUTRE, true, 'Oriane MORVAN - ALECOB');
        $this->setType(PartnerType::AUTRE, true, 'PLSF (S. TORNAVACCA)');
        $this->setType(PartnerType::AUTRE, true, 'Pôle Logement Social et Foncier');
    }

    private function setBailleurSocial()
    {
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur social');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur social');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'bailleurs sociaux GDH');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'bailleurs sociaux ORVITIS');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'bailleurs sociaux CDC HABITAT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'bailleurs sociaux HABELLIS');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'bailleurs sociaux ICF HABITAT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur social - PLURIAL NOVILIA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur social - NOV\'HABITAT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur social - REIMS HABITAT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur social - FOYER REMOIS');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur social - ICF');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur social');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Pau Béarn Habitat (bailleur social)');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, '3F SUD');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'HABITATIONS HAUTE PROVENCE');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'LOGIAH 04');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'UNICIL');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Ardèche habitat');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'HABITAT08');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, '13 HABITAT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Bailleur');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Ouest Provence Habitat');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'MARSEILLE HABITAT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Habitat Marseille Provence');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'HABITAT 17');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Hateis Habitat');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Val de berry - propriétaire bailleur');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'France Loire - Propriétaire bailleur');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'CORREZE HABITAT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Habitat Drouais');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Habitat Eurélien');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Chartres Métropole Habitat');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Nogent-Perche-Habitat');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SA Eure-et-Loir Habitat');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'ERILIA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'ERILIA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'ESH LOZERE HABITATIONS');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'FAMILLE ET PROVENCE');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'France Loire - Relogement');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Espacil Habitat');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Habitat du Nord');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'HOMY');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'ICF HABITAT NORD-EST (bailleur social)');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'ICF Méditerranée');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'IMMOBILIERE ATLANTIC AMENAGEMENT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'LOGIREM');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Logis Méditerranée');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SA HLM AIGUILLON CONSTRUCTION');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'ESPACIL HABITAT');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'NEOTOA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Alliade Habitat');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'OPH 66');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'OPH CDA LA ROCHELLE');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'OPH de la Meuse');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Partenord');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'PROCIVIS-SACICAP BSA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SA EMERAUDE HABITATION');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SA LA RANCE');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SA la Roseraie');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SA LES FOYERS');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SACOGIVA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SEDRE');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SEMAC');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SEMADER');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SEMIS');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SEMISAP');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SEMIVIM');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SFHE');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'LMH');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Logis Métropole');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, '3F Notre Logis');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Vilogia');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SHLMR');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SIBA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SIDR');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SIPHEM');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SODEGIS');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SODIAC');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SOGIMA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SIA Habitat (bailleur social)');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'SOLIHA (Bailleur social)');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'HSA (Bailleur Social)');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'DOMOFRANCE (bailleur social)');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'UNICIL');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'VILOGIA');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Domofrance');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Mesolia');
        $this->setType(PartnerType::BAILLEUR_SOCIAL, true, 'Perigord Habitat');
    }

    private function setConciliateurs()
    {
        $this->setType(PartnerType::CONCILIATEURS, true, 'Commission de conciliation');
        $this->setType(PartnerType::CONCILIATEURS, true, 'Conciliateur de justice (en attente)');
        $this->setType(PartnerType::CONCILIATEURS, true, 'Commission Départementale de Conciliation (CDC)');
        $this->setType(PartnerType::CONCILIATEURS, true, 'DDETSPP/DALO ou commission départemental de conciliation');
        $this->setType(PartnerType::CONCILIATEURS, true, 'DDTM - Commission de conciliation');
        $this->setType(PartnerType::CONCILIATEURS, true, 'Conciliateur de justice');
        $this->setType(PartnerType::CONCILIATEURS, true, 'conciliateur de justice');
        $this->setType(PartnerType::CONCILIATEURS, true, 'Commission de conciliation - DDETS');
        $this->setType(PartnerType::CONCILIATEURS, true, 'Conciliateur de justice');
        $this->setType(PartnerType::CONCILIATEURS, true, 'DDETS - Conciliation');
        $this->setType(PartnerType::CONCILIATEURS, true, 'Médiateur urbain');
        $this->setType(PartnerType::CONCILIATEURS, true, 'commission de conciliation');
        $this->setType(PartnerType::CONCILIATEURS, true, 'conciliateur de justice');
    }

    private function setEPCI()
    {
        $this->setType(PartnerType::EPCI, true, 'EPCI - CARF');
        $this->setType(PartnerType::EPCI, true, 'EPCI – CAPG');
        $this->setType(PartnerType::EPCI, true, 'EPCI - CACPL');
        $this->setType(PartnerType::EPCI, true, 'CA Annonay Rhône Agglo');
        $this->setType(PartnerType::EPCI, true, 'CC Rhone Crussol');
        $this->setType(PartnerType::EPCI, true, 'CA Arche Agglo');
        $this->setType(PartnerType::EPCI, true, 'CC Val’Eyrieux');
        $this->setType(PartnerType::EPCI, true, 'CA Privas-Centre-Ardèche');
        $this->setType(PartnerType::EPCI, true, 'CC Val de Ligne');
        $this->setType(PartnerType::EPCI, true, 'CC Berg et Coiron');
        $this->setType(PartnerType::EPCI, true, 'CC Du Rhône Aux Gorges de l\'Ardèche');
        $this->setType(PartnerType::EPCI, true, 'CC Ardèche Rhône Coiron');
        $this->setType(PartnerType::EPCI, true, 'CC du Bassin d\'Aubenas');
        $this->setType(PartnerType::EPCI, true, 'CDC Montagne Ardéchoise');
        $this->setType(PartnerType::EPCI, true, 'Bourges Plus');
        $this->setType(PartnerType::EPCI, true, 'OPAH Sancerre Sologne');
        $this->setType(PartnerType::EPCI, true, 'OPAH Pays Berry Saint Amandois');
        $this->setType(PartnerType::EPCI, true, 'communauté de commune du CAP CORSE');
        $this->setType(PartnerType::EPCI, true, 'Dijon métropole');
        $this->setType(PartnerType::EPCI, true, 'Métropole Chartres');
        $this->setType(PartnerType::EPCI, true, 'CC du Grand Châteaudun');
        $this->setType(PartnerType::EPCI, true, 'CC du Perche');
        $this->setType(PartnerType::EPCI, true, 'Sicoval');
        $this->setType(PartnerType::EPCI, true, 'Communauté de communes du Volvestre');
        $this->setType(PartnerType::EPCI, true, 'Bordeaux Métropole');
        $this->setType(PartnerType::EPCI, true, 'CA de Béziers-Méditerranée');
        $this->setType(PartnerType::EPCI, true, 'CC La Domitienne');
        $this->setType(PartnerType::EPCI, true, 'CA Hérault-Méditerranée');
        $this->setType(PartnerType::EPCI, true, 'CC du Pays de Lunel');
        $this->setType(PartnerType::EPCI, true, 'CC Vallée de l\'Hérault');
        $this->setType(PartnerType::EPCI, true, 'CD 34 PIG Départemental');
        $this->setType(PartnerType::EPCI, true, 'Pays Haut Languedoc et vignoble');
        $this->setType(PartnerType::EPCI, true, 'CC Lodévois et Larzac');
        $this->setType(PartnerType::EPCI, true, 'CC du Grand Pic Saint-Loup');
        $this->setType(PartnerType::EPCI, true, 'ARS Occitanie - DD34');
        $this->setType(PartnerType::EPCI, true, '3M MONTPELLIER - MEDITERRANEE - METROPOLE');
        $this->setType(PartnerType::EPCI, true, 'SETE AGGLOPOLE MEDITERRANEE');
        $this->setType(PartnerType::EPCI, true, 'CC Sud Hérault');
        $this->setType(PartnerType::EPCI, true, 'CC Avant-Monts');
        $this->setType(PartnerType::EPCI, true, 'PETR HAUTES TERRES d\'OC');
        $this->setType(PartnerType::EPCI, true, 'CC MONTS DE LACAUNE');
        $this->setType(PartnerType::EPCI, true, 'CC BRETAGNE ROMANTIQUE');
        $this->setType(PartnerType::EPCI, true, 'CCBD');
        $this->setType(PartnerType::EPCI, true, 'Grenoble-Alpes Métropole');
        $this->setType(PartnerType::EPCI, true, 'Nantes Métropole/SCHS');
        $this->setType(PartnerType::EPCI, true, 'CCEG');
        $this->setType(PartnerType::EPCI, true, 'CC Cévennes Mont Lozère');
        $this->setType(PartnerType::EPCI, true, 'CC Terres d\'Apcher Margeride Aubrac');
        $this->setType(PartnerType::EPCI, true, 'CC du Gévaudan');
        $this->setType(PartnerType::EPCI, true, 'CC Haut Allier');
        $this->setType(PartnerType::EPCI, true, 'EPCI');
        $this->setType(PartnerType::EPCI, true, 'CD - Terres de Lorraine');
        $this->setType(PartnerType::EPCI, true, 'CD - territoire de Briey');
        $this->setType(PartnerType::EPCI, true, 'CD - territoire du Lunévillois');
        $this->setType(PartnerType::EPCI, true, 'CD - territoire de Longwy');
        $this->setType(PartnerType::EPCI, true, 'CCBP');
        $this->setType(PartnerType::EPCI, true, 'CCMM');
        $this->setType(PartnerType::EPCI, true, 'MGN');
        $this->setType(PartnerType::EPCI, true, 'CAPSO');
        $this->setType(PartnerType::EPCI, true, 'CA2BM');
        $this->setType(PartnerType::EPCI, true, 'CC7V');
        $this->setType(PartnerType::EPCI, true, 'CUA');
        $this->setType(PartnerType::EPCI, true, 'API');
        $this->setType(PartnerType::EPCI, true, 'BILLOM-CO');
        $this->setType(PartnerType::EPCI, true, 'Métropole de Clermont');
        $this->setType(PartnerType::EPCI, true, 'PAYS DE SAINT-ELOY');
        $this->setType(PartnerType::EPCI, true, 'RLV');
        $this->setType(PartnerType::EPCI, true, 'TDM');
        $this->setType(PartnerType::EPCI, true, 'Com Com Haut BEARN');
        $this->setType(PartnerType::EPCI, true, 'CCLO');
        $this->setType(PartnerType::EPCI, true, 'CAPBP sauf Pau');
        $this->setType(PartnerType::EPCI, true, 'PERPIGNAN MEDITERRANEE METROPOLE COMMUNAUTE URBAINE-');
        $this->setType(PartnerType::EPCI, true, 'CC des Albères, de la Côte Vermeille et de l\'Illibéris');
        $this->setType(PartnerType::EPCI, true, 'CC Roussillon-Conflent');
        $this->setType(PartnerType::EPCI, true, 'CC Pyrénées Cerdagne');
        $this->setType(PartnerType::EPCI, true, 'COMMUNAUTE DE COMMUNES DES ASPRES');
        $this->setType(PartnerType::EPCI, true, 'PYR CATALANES');
        $this->setType(PartnerType::EPCI, true, 'AGLY FENOUILLEDES');
        $this->setType(PartnerType::EPCI, true, 'Communauté de Communes Conflent Canigo');
        $this->setType(PartnerType::EPCI, true, 'Communauté de communes - Corbières Salanque Méditerranée');
        $this->setType(PartnerType::EPCI, true, 'Communauté de communes - Haut-Vallespir');
        $this->setType(PartnerType::EPCI, true, 'Communauté de communes - Sud Roussillon');
        $this->setType(PartnerType::EPCI, true, 'Communauté de communes - Vallespir');
        $this->setType(PartnerType::EPCI, true, 'COR');
        $this->setType(PartnerType::EPCI, true, 'EMHA - Métropole de Lyon');
        $this->setType(PartnerType::EPCI, true, 'TCO');
        $this->setType(PartnerType::EPCI, true, 'Annie PEURON - Directrice Centre Intercommunal Action Sociale - Poher Communauté');
        $this->setType(PartnerType::EPCI, true, 'ARDENNE METROPOLE');
        $this->setType(PartnerType::EPCI, true, 'CAPEV');
        $this->setType(PartnerType::EPCI, true, 'CARENE Saint Nazaire agglomération');
        $this->setType(PartnerType::EPCI, true, 'CC Ventadour Egletons Monédières');
        $this->setType(PartnerType::EPCI, true, 'collectivité de corse');
        $this->setType(PartnerType::EPCI, true, 'Commu. Agglo. Arles Crau Camargue Montagnette');
        $this->setType(PartnerType::EPCI, true, 'Conseiller Habitat - Haut Léon Communauté');
        $this->setType(PartnerType::EPCI, true, 'Erika DAGORN - Chargée Mission Habitat - Concarneau Cornouaille Agglomération');
        $this->setType(PartnerType::EPCI, true, 'La CALI');
        $this->setType(PartnerType::EPCI, true, 'Lena BOURHIS - Chargé de Mission Habitat - Poher Communauté');
        $this->setType(PartnerType::EPCI, true, 'M.A.M.P. - CT1 / EAH Marseille');
        $this->setType(PartnerType::EPCI, true, 'M.A.M.P. - CT2 Pays d Aix');
        $this->setType(PartnerType::EPCI, true, 'M.A.M.P. - CT3 Pays Salonais');
        $this->setType(PartnerType::EPCI, true, 'M.A.M.P. - CT4 Pays d’Aubagne et de l’Etoile');
        $this->setType(PartnerType::EPCI, true, 'M.A.M.P. - CT5 Pays Istres Ouest Provence');
        $this->setType(PartnerType::EPCI, true, 'M.A.M.P. - CT6 Pays de Martigues');
        $this->setType(PartnerType::EPCI, true, 'Maëlle BONIZEC - conseillère habitat - Communauté de Communes Cap Sizun');
        $this->setType(PartnerType::EPCI, true, 'MBA');
        $this->setType(PartnerType::EPCI, true, 'MEL_Service Habitat privé');
        $this->setType(PartnerType::EPCI, true, 'MEL_Service Habitat privé - LILLOIS-NORD');
        $this->setType(PartnerType::EPCI, true, 'MEL_Service Habitat privé - LYS-TOURQUENNOIS');
        $this->setType(PartnerType::EPCI, true, 'MEL_Service Habitat privé - ROUBAISIS-EST');
        $this->setType(PartnerType::EPCI, true, 'MEL_Service Habitat privé - SUD-WEPPES');
        $this->setType(PartnerType::EPCI, true, 'MEL_Service PLH');
        $this->setType(PartnerType::EPCI, true, 'MEL_unité FSL');
        $this->setType(PartnerType::EPCI, true, 'Mission Habitat - CC Pays de Landivisiau');
        $this->setType(PartnerType::EPCI, true, 'Quimperlé Communauté');
        $this->setType(PartnerType::EPCI, true, 'Service CAPV');
        $this->setType(PartnerType::EPCI, true, 'Service commun habitat');
        $this->setType(PartnerType::EPCI, true, 'Service Habitat - Communauté de Communes Lesneven Côte des Légendes');
        $this->setType(PartnerType::EPCI, true, 'Service Habitat - Douarnenez Communauté');
        $this->setType(PartnerType::EPCI, true, 'Service Habitat - Morlaix Communauté');
        $this->setType(PartnerType::EPCI, true, 'Service habitat privé Métropole de Toulouse');
        $this->setType(PartnerType::EPCI, true, 'Service Habitat VGA');
        $this->setType(PartnerType::EPCI, true, 'SPL du VELAY');
        $this->setType(PartnerType::EPCI, true, 'Terre de Provence Agglomération');
        $this->setType(PartnerType::EPCI, true, 'Thibaut ALNET - Chargé de Mission Habitat - Communauté de Communes du Pays Bigouden Sud et du Haut Pays Bigouden');
        $this->setType(PartnerType::EPCI, true, 'Val de berry - Relogement');
    }

    private function setDispositif()
    {
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'ALF (OPAH)');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'PIG CD');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH GBA Ain');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH CCPA Ain');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH HBA Ain');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Urbanis OPAH Ain');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH RU Coeur de ville Bourg');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH CCRAPC');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'ENERG ETHIQUE 04');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'ALEC Lozère Energie PIG/ OPAH');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'ALTAIR (opérateur OPAH-RU ORTHEZ)');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Soliha 26 pour Opah CC ARC');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Soliha 26 plan de sauvegarde Copro Beauregard Annonay');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'DDT Anah');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Anah');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'ANAH');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Citémetrie');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Citémétrie - Cyril BENARD');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Citémétrie - Paula RODRIGUEZ');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Délégation Anah 2A');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'EIE Est');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'EIE Hem roubaix');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'EIE Lille');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Eie Lys');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Eie Nord');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Eie Sud');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Eie Tourcoing');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Eie Weppes');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Nolwenn RAGEL - Chargé de visite eau énergie - Heol');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'GAREL - Opérateur Amelio France renov Sud & Weppes');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Inhari');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Maia Lille Métropole Sud Est');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'MHD CAPA');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAC Quimper Cornouaille');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAC43');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Opah');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH DE BASTIA');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH Haute Gironde');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH RU Vizille');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH/PIG OC TEHA');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'OPAH/PIG Soliha');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Opérateur Amelio - GRAAL');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Opérateur CDHAT');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Opérateur OPAH CAPV');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Operateur OPAH RU La cote Saint André');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Opérateur OPAH RU Voiron');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Opérateur OPAH VCA');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Opérateur OPAH-RU ST Marcellin');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Opérateur Ulisse énergie - dispositif Soleni');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'SOLIHA - Opérateur Amelio France renov');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Soliha (Opérateur PIG/OPAH/Marchés)');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Soliha PIG DM');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Urbam');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'URBANIS - AMO-Lot7- Amelio');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Urbanis - OPAH RU Lesneven');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'URBANIS - Opérateur Amelio France renov');
        $this->setType(PartnerType::DISPOSITIF_RENOVATION_HABITAT, true, 'Expertise & Patrimoine');
    }

    private function setConseilDepartemental()
    {
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD Ain');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD 08');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'conseil départemental');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Conseil Départemental');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Conseil départemental- Habitat logement');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD33');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD38');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD (Département de la Haute-Loire)');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD - Département');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD 66');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Adeline KERHERVE - AS CDAS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Anaïs Hervé - Assistante sociale - CDAS Landerneau');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Aude QUELFETER - AS CDAS Quimperlé');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'BREARD Emmanuelle');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'carole ALLAIN - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Caroline Le Gall - AS CDAS Landerneau');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD - Accompagnement social');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD - aménagement / Anah');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD - PIG/Habitat');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD - solidarité');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD / DTS Comminges / Coordonnatrice logement');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD / DTS Lauragais / Coordonnatrice logement');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD / DTS Nord/ Coordonnateur logement');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD / DTS Pays Sud Toulousain / Coordonnatrice logement');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD / DTS Toulouse / Coordonnatrice logement');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD 44 partenariat CARENE');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD 64 (uniquement si travaux ANAH-PIG)');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD89 UTS AUXERRE');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD89 UTS AVALLON');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD89 UTS JOIGNY MIGENNES');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD89 UTS SENS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD89 UTS TONNERRE');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD89 UTS TOUCY');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Cécile NEDELEC - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Christelle CATHELOT - AS CDAS Douarnenez');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Claire LE GALL - AS CDAS Concarneau');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CONSEIL DEPARTEMENTAL - sce logement');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CONSEIL DEPARTEMENTAL Service Habitat Privé');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CORNIL Capucine');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Delphine GUYO - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Delphine THEPAUT - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'DEPARTEMENT');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Département');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Département - Aide à la pierre Parc privé');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'DEPARTEMENT DT LA ROCHELLE RE AUNIS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'DEPARTEMENT DT Rochefort');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'DEPARTEMENT DT Royan');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Département du Rhône');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Dominique GUILLIEC - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'DT Haute Saintonge');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'DT Saintes');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'DT VALS DE SAINTONGE');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Elen LE TEUFF - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'CD -  Habitat logement (VEE)');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Geraldine LE BRIS - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'GUYOT Noémie');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Jean Christophe BOISSY - Chargé de visite eau énergie - CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Julie ESCOBAR - AS CDAS Quimperlé');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Lola ROHAN - AS CDAS Landivisiau');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Maëlle APPERE - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Marlène JAMBOU - AS CDAS Quimperlé');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS ARRAGEOIS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS ARTOIS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS AUDOMAROIS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS BOULONNAIS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS CALAISIS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS HÉNIN');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS LENS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS MONTREUILLOIS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MDS TERNOIS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Morgane Kermorgant - AS CDAS Lannilis');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD ARGENTAT');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD BORT-LES-ORGUES');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD BRIVE CENTRE');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD BRIVE EST');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD BRIVE OUEST');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD EGLETONS');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD JUILLAC');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD MEYMAC');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD MEYSSAC');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD TULLE');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD USSEL');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'MSD UZERCHE');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'SAIFI Raymonde');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Sylvia COGNAC - AS CDAS Morlaix');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Sylvie PEOCH - AS CDAS de Quimper');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'Tiphaine CARADEC - Conseillère Logement CD29');
        $this->setType(PartnerType::CONSEIL_DEPARTEMENTAL, true, 'UTPAS de Villeneuve d Ascq');
    }

    public function up(Schema $schema): void
    {
        $this->setCommunes();
        $this->setAdil();
        $this->setARS();
        $this->setCAF();
        $this->setCCAS();
        $this->setDDETS();
        $this->setDDTM();
        $this->setOperateurs();
        $this->setPolice();
        $this->setPrefecture();
        $this->setTribunal();
        $this->setAssociation();
        $this->setAutre();
        $this->setBailleurSocial();
        $this->setConciliateurs();
        $this->setEPCI();
        $this->setDispositif();
        $this->setConseilDepartemental();
    }

    public function down(Schema $schema): void
    {
    }
}
