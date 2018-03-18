<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlaceRepository")
 * @Gedmo\Loggable
 */
class Diocese
{
    use TimestampableTrait;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Parish", mappedBy="diocese")
     */
    public $parishes;

    /**
     * @var string Messe Info id
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $code;

    /**
     * @var string Messe Info name
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $name;

    /**
     * @var string Messe Info website
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $url;

    /**
     * @var string Messe Info sector -> Google Maps country
     *
     * @ORM\Column(type="string", length=2, nullable=true)
     * @Gedmo\Versioned
     */
    public $country;

    /**
     * @var string Messe Info sector -> Google Maps administrative_area_level_1
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $region;

    /**
     * @var string Messe Info sector -> Google Maps
     *
     * @ORM\Column(type="float", nullable=true)
     * @Gedmo\Versioned
     */
    public $latitude;

    /**
     * @var string Messe Info sector -> Google Maps
     *
     * @ORM\Column(type="float", nullable=true)
     * @Gedmo\Versioned
     */
    public $longitude;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->name . ' (' . $this->region . ')';
    }
}
