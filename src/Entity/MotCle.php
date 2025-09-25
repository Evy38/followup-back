<?php

namespace App\Entity;

use App\Repository\MotCleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MotCleRepository::class)]
class MotCle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, Relance>
     */
    #[ORM\ManyToMany(targetEntity: Relance::class, mappedBy: 'motsCles')]
    private Collection $relances;

    /**
     * @var Collection<int, Reponse>
     */
    #[ORM\ManyToMany(targetEntity: Reponse::class, mappedBy: 'motsCles')]
    private Collection $reponses;


    public function __construct()
    {
        $this->relances = new ArrayCollection();
        $this->reponses = new ArrayCollection();
    }

    // --- Getters / Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    /**
     * @return Collection<int, Relance>
     */
    public function getRelances(): Collection
    {
        return $this->relances;
    }

    public function addRelance(Relance $relance): static
    {
        if (!$this->relances->contains($relance)) {
            $this->relances->add($relance);
            $relance->addMotCle($this);
        }
        return $this;
    }

    public function removeRelance(Relance $relance): static
    {
        if ($this->relances->removeElement($relance)) {
            $relance->removeMotCle($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->addMotCle($this);
        }
        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            $reponse->removeMotCle($this);
        }
        return $this;
    }
}
