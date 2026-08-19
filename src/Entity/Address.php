<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use App\Utils\Address\CommuneHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_address_housenumber_street_postcode_citycode', columns: ['housenumber', 'street', 'post_code', 'city_code'])]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $housenumber = null;

    #[ORM\Column(length: 100)]
    #[Assert\Length(max: 100)]
    private string $street = '';

    #[ORM\Column(length: 100)]
    #[Assert\Length(max: 100)]
    private string $city = '';

    #[ORM\Column(type: 'string', length: 5)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.')]
    private string $postCode = '';

    #[ORM\Column(type: 'string', length: 5)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^(?:\d{5}|2[AB]\d{3})$/i',
        message: 'Le code INSEE doit être au format 5 chiffres (ex: 13201) ou 2A/2B + 3 chiffres (ex: 2A004).'
    )]
    private string $cityCode = '';

    /**
     * Identifiant BAN (Base Adresse Nationale) de l'adresse de l'occupant.
     *
     * Cette valeur est utilisée pour interroger
     * le Référentiel National des Bâtiments (RNB) afin de retrouver
     * les bâtiments associés à l'adresse.
     *
     * Attention : il s'agit de la clé d'interopérabilité BAN (ex : 13202_2333_00025)
     * et non de l'uuid technique de la BAN dit `banId`
     */
    #[ORM\Column(length: 100, unique: true, nullable: true)]
    private ?string $banId = null;

    #[ORM\Column(type: 'point', nullable: true)]
    private ?Point $point = null;

    /**
     * @var Collection<int, Arrete>
     */
    #[ORM\OneToMany(targetEntity: Arrete::class, mappedBy: 'address')]
    private Collection $arretes;

    /**
     * @var Collection<int, Signalement>
     */
    #[ORM\OneToMany(targetEntity: Signalement::class, mappedBy: 'address')]
    private Collection $signalements;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private Territory $territory;

    public function __construct()
    {
        $this->arretes = new ArrayCollection();
        $this->signalements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHousenumber(): ?string
    {
        return $this->housenumber;
    }

    public function setHousenumber(?string $housenumber): static
    {
        $this->housenumber = $housenumber;

        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function setPostCode(string $postCode): static
    {
        $this->postCode = $postCode;

        return $this;
    }

    public function getCityCode(): string
    {
        return $this->cityCode;
    }

    public function setCityCode(string $cityCode): static
    {
        $this->cityCode = $cityCode;

        return $this;
    }

    public function getBanId(): ?string
    {
        return $this->banId;
    }

    public function setBanId(?string $banId): static
    {
        $this->banId = $banId;

        return $this;
    }

    public function getPoint(): ?Point
    {
        return $this->point;
    }

    public function setPoint(?Point $point): static
    {
        $this->point = $point;

        return $this;
    }

    /**
     * @return Collection<int, Arrete>
     */
    public function getArretes(): Collection
    {
        return $this->arretes;
    }

    public function addArrete(Arrete $arrete): static
    {
        if (!$this->arretes->contains($arrete)) {
            $this->arretes->add($arrete);
            $arrete->setAddress($this);
        }

        return $this;
    }

    public function removeArrete(Arrete $arrete): static
    {
        $this->arretes->removeElement($arrete);

        return $this;
    }

    /**
     * @return Collection<int, Signalement>
     */
    public function getSignalements(): Collection
    {
        return $this->signalements;
    }

    public function addSignalement(Signalement $signalement): static
    {
        if (!$this->signalements->contains($signalement)) {
            $this->signalements->add($signalement);
            $signalement->setAddress($this);
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): static
    {
        if ($this->signalements->removeElement($signalement) && $signalement->getAddress() === $this) {
            $signalement->setAddress(null);
        }

        return $this;
    }

    public function getTerritory(): Territory
    {
        return $this->territory;
    }

    public function setTerritory(Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }

    public function getFull(bool $withArrondisement = true): string
    {
        $city = $withArrondisement ? $this->city : CommuneHelper::getCommuneFromArrondissement($this->city);

        return mb_trim(sprintf(
            '%s %s %s %s',
            $this->housenumber,
            $this->street,
            $this->postCode,
            $city
        ));
    }

    public function getHousenumberAndStreet(): string
    {
        return mb_trim(sprintf(
            '%s %s',
            $this->housenumber,
            $this->street
        ));
    }
}
