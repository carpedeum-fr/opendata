<?php

namespace App\Entity;

use Doctrine\DBAL\Types\DateType;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class Time
{
    const DAYS_OF_WEEK = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Place", inversedBy="timetable")
     */
    public $place;

    /**
     * @var DateType
     *
     * @ORM\Column(name="day_of_week", type="string", nullable=false)
     * @Gedmo\Versioned
     * @Assert\Choice({"", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"})
     */
    public $dayOfWeek;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="time", nullable=false)
     * @Gedmo\Versioned
     * @Assert\NotBlank
     */
    public $time;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", length=65535, nullable=false)
     * @Gedmo\Versioned
     */
    public $notes;

    public function getId(): ?int
    {
        return $this->id;
    }
}
