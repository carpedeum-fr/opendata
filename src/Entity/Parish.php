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
class Parish
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
     * @ORM\OneToMany(targetEntity="\App\Entity\Address", mappedBy="parish")
     */
    public $addresses;

    /**
     * @var Address
     *
     * @ORM\OneToOne(targetEntity="\App\Entity\Address")
     */
    public $addresseMesseInfo;

    /**
     * @var Address
     *
     * @ORM\OneToOne(targetEntity="\App\Entity\Address")
     */
    public $addresseGoogleMaps;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Diocese", inversedBy="parishes")
     */
    public $diocese;

    /**
     * @var string Messe Info id
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $code;

    /**
     * @var string Messe Info alias
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $alias;

    /**
     * @var string Messe Info name
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $name;

    /**
     * @var string Messe Info alias
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $type;

    /**
     * @var string Messe Info responsible
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $responsible;

    /**
     * @var string Messe Info description
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Versioned
     */
    public $description;

    /**
     * @var string Messe Info alias
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $email;

    /**
     * @var string Messe Info alias
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $phoneOriginal;

    /**
     * @var string libphonenumber PhoneNumberFormat::E164
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $phone;

    /**
     * @var string libphonenumber PhoneNumberFormat::INTERNATIONAL
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $phoneInternational;

    /**
     * @var string libphonenumber PhoneNumberFormat::NATIONAL
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $phoneNational;

    /**
     * @var string Messe Info alias
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $url;

    /**
     * @var string Messe Info common name
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $commonName;

    /**
     * @var string Messe Info picture
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $picture;

    /**
     * @var string Messe Info tags
     *
     * @ORM\Column(nullable=true)
     * @Gedmo\Versioned
     */
    public $tags;

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
