<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private ?int $spoonacularId = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $caloriesParPortion = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $proteinG = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $carbsG = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $fatG = null;

    #[ORM\Column]
    private ?int $servings = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rawJson = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ElementRepas>
     */
    #[ORM\OneToMany(mappedBy: 'recette', targetEntity: ElementRepas::class)]
    private Collection $elementsRepas;

    public function __construct()
    {
        $this->elementsRepas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpoonacularId(): ?int
    {
        return $this->spoonacularId;
    }

    public function setSpoonacularId(int $spoonacularId): static
    {
        $this->spoonacularId = $spoonacularId;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getCaloriesParPortion(): ?int
    {
        return $this->caloriesParPortion;
    }

    public function setCaloriesParPortion(?int $caloriesParPortion): static
    {
        $this->caloriesParPortion = $caloriesParPortion;

        return $this;
    }

    public function getProteinG(): ?string
    {
        return $this->proteinG;
    }

    public function setProteinG(?string $proteinG): static
    {
        $this->proteinG = $proteinG;

        return $this;
    }

    public function getCarbsG(): ?string
    {
        return $this->carbsG;
    }

    public function setCarbsG(?string $carbsG): static
    {
        $this->carbsG = $carbsG;

        return $this;
    }

    public function getFatG(): ?string
    {
        return $this->fatG;
    }

    public function setFatG(?string $fatG): static
    {
        $this->fatG = $fatG;

        return $this;
    }

    public function getServings(): ?int
    {
        return $this->servings;
    }

    public function setServings(int $servings): static
    {
        $this->servings = $servings;

        return $this;
    }

    public function getRawJson(): ?array
    {
        return $this->rawJson;
    }

    public function setRawJson(?array $rawJson): static
    {
        $this->rawJson = $rawJson;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, ElementRepas>
     */
    public function getElementsRepas(): Collection
    {
        return $this->elementsRepas;
    }

    public function addElementRepas(ElementRepas $elementRepas): static
    {
        if (!$this->elementsRepas->contains($elementRepas)) {
            $this->elementsRepas->add($elementRepas);
            $elementRepas->setRecette($this);
        }

        return $this;
    }

    public function removeElementRepas(ElementRepas $elementRepas): static
    {
        if ($this->elementsRepas->removeElement($elementRepas)) {
            if ($elementRepas->getRecette() === $this) {
                $elementRepas->setRecette(null);
            }
        }

        return $this;
    }
}
