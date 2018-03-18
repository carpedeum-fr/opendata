<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlaceRepository")
 * @Gedmo\Loggable
 */
class Address
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
     * @ORM\ManyToOne(targetEntity="\App\Entity\Parish", inversedBy="addresses")
     */
    public $parish;


    /**
     * @var string The address name.
     *
     * @ORM\Column
     * @Gedmo\Versioned
     */
    public $name;


    /**
     * @var string The country. For example, USA. You can also provide the two-letter ISO 3166-1 alpha-2 country code.
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\Choice(callback="getCountry")
     */
    public $addressCountry = 'FR';


    /**
     * @var string The locality. For example, Mountain View.
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $addressLocality;


    /**
     * @var string The region. For example, CA.
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $addressRegion;


    /**
     * @var string The postal code. For example, 94043.
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $postalCode;


    /**
     * @var string The street address. For example, 1600 Amphitheatre Pkwy.
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $streetAddress;


    /**
     * @var string The formatted street address. For example, 12 Rue de l'Église, 13290 Aix-en-Provence, France
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $formattedAddress;


    /**
     * @ORM\Column(type="float", nullable=true)
     * @Gedmo\Versioned
     */
    public $latitude;


    /**
     * @ORM\Column(type="float", nullable=true)
     * @Gedmo\Versioned
     */
    public $longitude;


    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    public $zoom;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->name;
    }
}
