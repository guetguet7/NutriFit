<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\ActiviteRepository::class)]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $utilisateur = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['course', 'marche', 'velo', 'natation'])]
    private ?string $type = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $dureeMin = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $caloriesBrulees = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $dateActivite = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDureeMin(): ?int
    {
        return $this->dureeMin;
    }

    public function setDureeMin(int $dureeMin): static
    {
        $this->dureeMin = $dureeMin;

        return $this;
    }

    public function getCaloriesBrulees(): ?int
    {
        return $this->caloriesBrulees;
    }

    public function setCaloriesBrulees(int $caloriesBrulees): static
    {
        $this->caloriesBrulees = $caloriesBrulees;

        return $this;
    }

    public function getDateActivite(): ?\DateTimeImmutable
    {
        return $this->dateActivite;
    }

    public function setDateActivite(\DateTimeImmutable $dateActivite): static
    {
        $this->dateActivite = $dateActivite;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
