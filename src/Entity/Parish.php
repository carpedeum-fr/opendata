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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->name;
    }
}
