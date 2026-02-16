<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class ElementRepas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'elementsRepas')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Repas $repas = null;

    #[ORM\ManyToOne(inversedBy: 'elementsRepas')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Recette $recette = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $quantite = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $unite = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $calories = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRepas(): ?Repas
    {
        return $this->repas;
    }

    public function setRepas(?Repas $repas): static
    {
        $this->repas = $repas;

        return $this;
    }

    public function getRecette(): ?Recette
    {
        return $this->recette;
    }

    public function setRecette(?Recette $recette): static
    {
        $this->recette = $recette;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(string $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): static
    {
        $this->unite = $unite;

        return $this;
    }

    public function getCalories(): ?int
    {
        return $this->calories;
    }

    public function setCalories(int $calories): static
    {
        $this->calories = $calories;

        return $this;
    }
}
