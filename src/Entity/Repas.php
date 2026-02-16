<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\RepasRepository::class)]
class Repas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $utilisateur = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $dateRepas = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['petit_dej', 'dejeuner', 'diner', 'collation'])]
    private ?string $typeRepas = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $totalCalories = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['api', 'manuel'])]
    private ?string $source = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, ElementRepas>
     */
    #[ORM\OneToMany(mappedBy: 'repas', targetEntity: ElementRepas::class, orphanRemoval: true, cascade: ['persist'])]
    #[Assert\Valid]
    private Collection $elementsRepas;

    public function __construct()
    {
        $this->elementsRepas = new ArrayCollection();
    }

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

    public function getDateRepas(): ?\DateTimeImmutable
    {
        return $this->dateRepas;
    }

    public function setDateRepas(\DateTimeImmutable $dateRepas): static
    {
        $this->dateRepas = $dateRepas;

        return $this;
    }

    public function getTypeRepas(): ?string
    {
        return $this->typeRepas;
    }

    public function setTypeRepas(string $typeRepas): static
    {
        $this->typeRepas = $typeRepas;

        return $this;
    }

    public function getTotalCalories(): ?int
    {
        return $this->totalCalories;
    }

    public function setTotalCalories(int $totalCalories): static
    {
        $this->totalCalories = $totalCalories;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

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
            $elementRepas->setRepas($this);
        }

        return $this;
    }

    public function removeElementRepas(ElementRepas $elementRepas): static
    {
        if ($this->elementsRepas->removeElement($elementRepas)) {
            if ($elementRepas->getRepas() === $this) {
                $elementRepas->setRepas(null);
            }
        }

        return $this;
    }
}
