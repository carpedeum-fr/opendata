<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class Time
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $messeInfoId;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Place", inversedBy="timetable")
     */
    public $place;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datetime", type="datetime", nullable=false)
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $datetime;

    /**
     * @var string
     *
     * @ORM\Column
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $length;

    /**
     * @var string
     *
     * @ORM\Column(nullable=True)
     * @Gedmo\Versioned
     */
    public $timeType;

    /**
     * @var string
     *
     * @ORM\Column(nullable=True)
     * @Gedmo\Versioned
     */
    public $celebrationType;

    /**
     * @var string
     *
     * @ORM\Column(nullable=True)
     * @Gedmo\Versioned
     */
    public $recurrenceCategory;

    /**
     * @var string
     *
     * @ORM\Column(nullable=True)
     * @Gedmo\Versioned
     */
    public $notes;

    /**
     * @var string
     *
     * @ORM\Column
     * @Gedmo\Versioned
     */
    public $tags;

    /**
     * @var bool
     *
     * @ORM\Column
     * @Gedmo\Versioned
     */
    public $isValid;

    /**
     * @var bool
     *
     * @ORM\Column
     * @Gedmo\Versioned
     */
    public $isActive;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->datetime->format('D d F, G\hi');
    }
}
