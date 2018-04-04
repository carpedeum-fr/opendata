<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlaceRepository")
 * @Gedmo\Loggable
 */
class Place
{
    use TimestampableTrait;

    public static function getCountry()
    {
        \Locale::setDefault('en');
        return array_flip(Intl::getRegionBundle()->getCountryNames());
    }

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
     * @ORM\OneToMany(targetEntity="\App\Entity\Address", mappedBy="place")
     */
    public $addresses;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="\App\Entity\Time", mappedBy="place")
     */
    public $timetable;

    /**
     * @var string A place name
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $name = '';

    /**
     * @var string Slug of the place name
     *
     * @ORM\Column
     * @Gedmo\Slug(fields={"name", "addressLocality"})
     * @Assert\NotBlank
     */
    public $slug = '';

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
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
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
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $postalCode;


    /**
     * @var string The street address. For example, 1600 Amphitheatre Pkwy.
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
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

    /**
     * @var string Messe Info picture
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $picture;

    /**
     * @var string Messe Info alias
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->name . ' (' . $this->addressLocality . ')';
    }
}
