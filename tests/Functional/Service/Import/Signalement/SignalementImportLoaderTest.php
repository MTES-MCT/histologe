<?php

namespace App\Tests\Functional\Service\Import\Signalement;

use App\Entity\Enum\MotifCloture;
use App\Entity\Territory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\TagManager;
use App\Service\Import\Signalement\SignalementImportLoader;
use App\Service\Import\Signalement\SignalementImportMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Faker\Factory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalementImportLoaderTest extends KernelTestCase
{
    private SignalementImportMapper $signalementImportMapper;
    private SignalementManager $signalementManager;
    private TagManager $tagManager;
    private AffectationManager $affectationManager;
    private SuiviManager $suiviManager;
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->signalementImportMapper = self::getContainer()->get(SignalementImportMapper::class);
        $this->signalementManager = self::getContainer()->get(SignalementManager::class);
        $this->tagManager = self::getContainer()->get(TagManager::class);
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
        $this->suiviManager = self::getContainer()->get(SuiviManager::class);
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $this->logger = self::getContainer()->get(LoggerInterface::class);

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testLoadSignalementImport()
    {
        $signalementImportLoader = new SignalementImportLoader(
            $this->signalementImportMapper,
            $this->signalementManager,
            $this->tagManager,
            $this->affectationManager,
            $this->suiviManager,
            $this->entityManager,
            $this->parameterBag,
            $this->logger,
        );

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '01']);
        $headers = array_keys($this->getData()[0]);
        $signalementImportLoader->load($territory, $this->getData(), $headers);

        $this->assertArrayHasKey('count_signalement', $signalementImportLoader->getMetadata());
        $this->assertEquals(10, $signalementImportLoader->getMetadata()['count_signalement']);
    }

    public function getData(): array
    {
        $faker = Factory::create('fr_FR');
        $dataList = [];
        for ($i = 0; $i < 10; ++$i) {
            $dataItem = [
                'Ref signalement' => (0 === $i % 2) ? $faker->randomNumber(4) : '2022-'.$faker->randomNumber(4),
                'Date de creation signalement' => '22/12/2022',
                'Date cloture' => null,
                'motif_cloture' => (0 === $i % 2) ? MotifCloture::LABEL['AUTRE'] : null,
                'ref des photos' => null,
                'ref des documents' => null,
                'details' => $faker->realText(),
                'Propriétaire averti' => false,
                'Date proprietaire averti' => null,
                'nb d\'adultes' => $faker->randomDigit(),
                'nb d enfants <6ans' => $faker->randomDigit(),
                'nb d enfants >6ans' => $faker->randomDigit(),
                'nb occupants logement' => $faker->randomDigit(),
                'Allocataire' => false,
                'numéro Allocataire' => null,
                'type logement' => 'maison',
                'superficie' => $faker->randomNumber(),
                'Nom propriétaire' => $faker->lastName(),
                'Adresse Propriétaire' => null,
                'Telephone Proprietaire' => null,
                'Mail Propriétaire' => null,
                'Logement social' => true,
                'Préavis de départ donné' => false,
                'Demande de Relogement en cours?' => false,
                'Déclarant est l occupant?' => true,
                'nom du declarant' => null,
                'prenom declarant' => null,
                'telephone declarant' => (0 === $i % 3) ? '0611121314' : '611121314',
                'mail declarant' => null,
                'lien entre declarant et occupant' => null,
                'nom structure declarant si tiers professionnel' => null,
                'nom occupant' => $faker->lastName(),
                'prenom occupant' => $faker->firstName(),
                'telephone occupant' => '0611121315',
                'mail occupant' => $faker->email(),
                'adresse occupant' => $faker->address(),
                'Code postal occupant' => '13001',
                'ville occupant' => $faker->city(),
                'code insee occupant' => '13001',
                'date visite' => null,
                'Occupant présent lors de la visite ?' => null,
                'etage occupant' => '2',
                'escalier occupant' => null,
                'numéro appartement  occupant' => null,
                'mode contact propriétaire  ?' => null,
                'RSA' => false,
                'Logement < 1948' => false,
                'Fond solidarite logement' => false,
                'Risque de suroccupation' => false,
                'numero invariant' => null,
                'Nature du logement' => 'appartement',
                'loyer' => 1000,
                'Bail en cours' => true,
                'date entree bail' => null,
                'Occupant Accepte visite/travaux ?' => false,
                'Occupant refuse visite/ Motif' => null,
                'CGU acceptees' => false,
                'Date modification / maj' => null,
                'statut' => (0 === $i % 2) ? 'en cours' : 'fermeture',
                'geoloc' => null,
                'montant allocation' => null,
                'code procedure en cours' => null,
                'adresse_autre_occupant' => null,
                'Accord occupant declaration par tiers' => false,
                'annee construction immeuble' => null,
                'type energie logement' => null,
                'origine signalement' => null,
                'situation occupant' => null,
                'situation pro occupant' => null,
                'naissance occupant' => 'A partir de 1980',
                'logement collectif' => true,
                'nom du referent social' => null,
                'structure referent social' => null,
                'mail syndic' => null,
                'telelephone syndic' => null,
                'nom syndic' => null,
                'nom sci' => null,
                'nom representant sci' => null,
                'telephone sci' => null,
                'mail sci' => null,
                'nb de pieces du logement' => $faker->randomDigit(),
                'nb chambres logement' => $faker->randomDigit(),
                'nb niveaux logement' => $faker->randomDigit(),
                'qualification' => 'police',
                'Partenaires à affecter' => 'Partenaire 01-01, Partenaire 01-02, Partenaire 01-03',
                'Signalement - Securite occupants 1' => 'humidité',
                'Signalement - Securite occupants 2' => null,
                'Signalement - Securite occupants 3' => null,
                'Signalement - Etat & Proprete logement 1' => 'il y a régulièrement des traces d’humidité',
                'Signalement - Etat & Proprete logement 2' => null,
                'Signalement - Etat & Proprete logement 3' => null,
                'Signalement - Confort logement 1' => 'le chauffage est insuffisant, la chaleur ressentie est trop faible',
                'Signalement - Confort logement 2' => null,
                'Signalement - Confort logement 3' => null,
                'Signalement - Etat batiment 1' => "la peinture est écaillée et présente quelques traces d'humidité",
                'Signalement - Etat batiment 2' => null,
                'Signalement - Etat batiment 3' => null,
                'Signalement - Espaces de vie 1' => "mon logement est mal isolé et j'ai du mal à y vivre",
                'Signalement - Espaces de vie 2' => null,
                'Signalement - Espaces de vie 3' => null,
                'Signalement - Vie commune & voisinage 1' => 'L’usage des lieux n’est pas respecté - mauvais état',
                'Signalement - Vie commune & voisinage 2' => null,
                'Signalement - Vie commune & voisinage 3' => null,
                'suivi' => '2022/01/11 ouverture CAF : mandat Soliha Visite Soliha, CAF - 2022/07/26 en cours Point - ',
            ];
            $dataList[] = $dataItem;
        }
        $dataList[] = ['Ref signalement' => null];

        return $dataList;
    }
}
