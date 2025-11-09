<?php

namespace App\Entity;

use App\Repository\MotCleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MotCleRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['motcle:read']]),
        new GetCollection(normalizationContext: ['groups' => ['motcle:read']]),
        new Post(denormalizationContext: ['groups' => ['motcle:write']]),
        new Put(denormalizationContext: ['groups' => ['motcle:write']]),
        new Delete()
    ],
    security: "is_granted('ROLE_USER')"
)]
class MotCle
{
       #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['motcle:read', 'candidature:read', 'candidature:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['motcle:read', 'motcle:write', 'candidature:read', 'candidature:write'])]
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

    #[ORM\ManyToMany(targetEntity: Candidature::class, mappedBy: 'motsCles')]
    private Collection $candidatures;


    public function __construct()
    {
        $this->relances = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->candidatures = new ArrayCollection();
    }

    // --- Getters / Setters ---

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): static
    {
        if (!$this->candidatures->contains($candidature)) {
            $this->candidatures->add($candidature);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        $this->candidatures->removeElement($candidature);
        return $this;
    }


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
