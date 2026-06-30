<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LongitudeOne\Spatial\PHP\Types\SpatialInterface;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_address_housenumber_street_citycode', columns: ['housenumber', 'street', 'city_code'])]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $housenumber = null;

    #[ORM\Column(length: 100)]
    private string $street = '';

    #[ORM\Column(length: 100)]
    private string $city = '';

    #[ORM\Column(type: 'string', length: 5)]
    private string $postCode = '';

    #[ORM\Column(type: 'string', length: 5)]
    private string $cityCode = '';

    #[ORM\Column(length: 100, nullable: true, unique: true)]
    private ?string $banId = null;

    #[ORM\Column(type: 'point', nullable: true)]
    private ?SpatialInterface $point = null;

    /**
     * @var Collection<int, Arrete>
     */
    #[ORM\OneToMany(targetEntity: Arrete::class, mappedBy: 'address')]
    private Collection $arretes;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private Territory $territory;

    public function __construct()
    {
        $this->arretes = new ArrayCollection();
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

    public function getPoint(): ?SpatialInterface
    {
        return $this->point;
    }

    public function setPoint(?SpatialInterface $point): static
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

    public function getTerritory(): Territory
    {
        return $this->territory;
    }

    public function setTerritory(Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }
}
