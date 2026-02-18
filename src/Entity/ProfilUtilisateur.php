<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class ProfilUtilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'profilUtilisateur')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Assert\NotNull]
    private ?User $utilisateur = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['homme', 'femme'])]
    private ?string $sexe = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 120)]
    private ?int $age = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 50, max: 280)]
    private ?int $tailleCm = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $poidsKg = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['sédentaire', 'léger', 'modéré', 'actif'])]
    private ?string $niveauActivite = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['perte', 'maintien', 'prise'])]
    private ?string $objectifType = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\Positive]
    private ?string $poidsCibleKg = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 2)]
    private ?string $rythmePerteKgSemaine = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $pseudo = null;

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

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getTailleCm(): ?int
    {
        return $this->tailleCm;
    }

    public function setTailleCm(?int $tailleCm): static
    {
        $this->tailleCm = $tailleCm;

        return $this;
    }

    public function getPoidsKg(): ?string
    {
        return $this->poidsKg;
    }

    public function setPoidsKg(?string $poidsKg): static
    {
        $this->poidsKg = $poidsKg;

        return $this;
    }

    public function getNiveauActivite(): ?string
    {
        return $this->niveauActivite;
    }

    public function setNiveauActivite(?string $niveauActivite): static
    {
        $this->niveauActivite = $niveauActivite;

        return $this;
    }

    public function getObjectifType(): ?string
    {
        return $this->objectifType;
    }

    public function setObjectifType(?string $objectifType): static
    {
        $this->objectifType = $objectifType;

        return $this;
    }

    public function getPoidsCibleKg(): ?string
    {
        return $this->poidsCibleKg;
    }

    public function setPoidsCibleKg(?string $poidsCibleKg): static
    {
        $this->poidsCibleKg = $poidsCibleKg;

        return $this;
    }

    public function getRythmePerteKgSemaine(): ?string
    {
        return $this->rythmePerteKgSemaine;
    }

    public function setRythmePerteKgSemaine(?string $rythmePerteKgSemaine): static
    {
        $this->rythmePerteKgSemaine = $rythmePerteKgSemaine;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }
}
