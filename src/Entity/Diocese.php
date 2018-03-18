<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlaceRepository")
 * @Gedmo\Loggable
 */
class Diocese
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

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
     * @ORM\Column(nullable=true)
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
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $latitude;

    /**
     * @var string Messe Info sector -> Google Maps
     *
     * @ORM\Column(nullable=true)
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
