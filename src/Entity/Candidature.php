<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ApiResource]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateCandidature = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienAnnonce = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    private ?Ville $ville = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    private ?Canal $canal = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Statut $statut = null;
    /**
     * @var Collection<int, Reponse>
     */
    #[ORM\OneToMany(mappedBy: 'candidature', targetEntity: Reponse::class, orphanRemoval: true)]
    private Collection $reponses;
    /**
     * @var Collection<int, MotCle>
     */
    #[ORM\ManyToMany(targetEntity: MotCle::class, inversedBy: 'candidatures')]
    private Collection $motsCles;



    /**
     * @var Collection<int, Relance>
     */
    #[ORM\OneToMany(targetEntity: Relance::class, mappedBy: 'candidature')]
    private Collection $relances;

    public function __construct()
    {
        $this->relances = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->motsCles = new ArrayCollection();
    }

    public function getMotsCles(): Collection
    {
        return $this->motsCles;
    }

    public function addMotCle(MotCle $motCle): static
    {
        if (!$this->motsCles->contains($motCle)) {
            $this->motsCles->add($motCle);
        }

        return $this;
    }

    public function removeMotCle(MotCle $motCle): static
    {
        $this->motsCles->removeElement($motCle);
        return $this;
    }


    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setCandidature($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getCandidature() === $this) {
                $reponse->setCandidature(null);
            }
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCandidature(): ?\DateTime
    {
        return $this->dateCandidature;
    }

    public function setDateCandidature(\DateTime $dateCandidature): static
    {
        $this->dateCandidature = $dateCandidature;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(?string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getLienAnnonce(): ?string
    {
        return $this->lienAnnonce;
    }

    public function setLienAnnonce(?string $lienAnnonce): static
    {
        $this->lienAnnonce = $lienAnnonce;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getVille(): ?Ville
    {
        return $this->ville;
    }

    public function setVille(?Ville $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCanal(): ?Canal
    {
        return $this->canal;
    }

    public function setCanal(?Canal $canal): static
    {
        $this->canal = $canal;

        return $this;
    }

    public function getStatut(): ?Statut
    {
        return $this->statut;
    }

    public function setStatut(?Statut $statut): static
    {
        $this->statut = $statut;

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
            $relance->setCandidature($this);
        }

        return $this;
    }

    public function removeRelance(Relance $relance): static
    {
        if ($this->relances->removeElement($relance)) {
            // set the owning side to null (unless already changed)
            if ($relance->getCandidature() === $this) {
                $relance->setCandidature(null);
            }
        }

        return $this;
    }
}
