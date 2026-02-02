<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            security: "object.getUser() == user or is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            securityPostDenormalize: "object.getUser() == user or is_granted('ROLE_ADMIN')"
        ),
        new Put(
            security: "object.getUser() == user or is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            security: "object.getUser() == user or is_granted('ROLE_ADMIN')",
            securityMessage: "Vous ne pouvez supprimer que vos propres candidatures."
        )
    ],
    normalizationContext: ['groups' => ['candidature:read']],
    denormalizationContext: ['groups' => ['candidature:write']]
)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['candidature:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['candidature:read'])]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $dateCandidature = null;

    #[ORM\Column(length: 255)]
    #[Groups(['candidature:read', 'candidature:write'])]
    #[Assert\NotBlank]
    private string $jobTitle;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['candidature:read'])]
    private ?string $lienAnnonce = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['candidature:read', 'candidature:write'])]
    private ?string $mode = null;

    #[ORM\Column(length: 100)]
    #[Groups(['candidature:read'])]
    #[Assert\NotBlank]
    private string $externalOfferId;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['candidature:read'])]
    private ?\DateTimeImmutable $dateDerniereRelance = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['candidature:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['candidature:read'])]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['candidature:read'])]
    private ?Statut $statut = null;

    #[ORM\OneToMany(
        mappedBy: 'candidature',
        targetEntity: Relance::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[Groups(['candidature:read'])]
    private Collection $relances;

    #[ORM\Column(length: 20, options: ['default' => 'attente'])]
    #[Groups(['candidature:read'])]
    private string $statutReponse = 'attente';

    #[ORM\OneToMany(
        mappedBy: 'candidature',
        targetEntity: Entretien::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[Groups(['candidature:read'])]
    private Collection $entretiens;

    public function __construct()
    {
        $this->relances = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
    }

    // ---------------- Getters / Setters ----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCandidature(): ?\DateTimeImmutable
    {
        return $this->dateCandidature;
    }
    public function setDateCandidature(\DateTimeImmutable $d): static
    {
        $this->dateCandidature = $d;
        return $this;
    }

    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }
    public function setJobTitle(string $t): static
    {
        $this->jobTitle = $t;
        return $this;
    }

    public function getLienAnnonce(): ?string
    {
        return $this->lienAnnonce;
    }
    public function setLienAnnonce(?string $l): static
    {
        $this->lienAnnonce = $l;
        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }
    public function setMode(?string $m): static
    {
        $this->mode = $m;
        return $this;
    }

    public function getExternalOfferId(): string
    {
        return $this->externalOfferId;
    }
    public function setExternalOfferId(string $id): static
    {
        $this->externalOfferId = $id;
        return $this;
    }

    public function getDateDerniereRelance(): ?\DateTimeImmutable
    {
        return $this->dateDerniereRelance;
    }
    public function setDateDerniereRelance(?\DateTimeImmutable $d): static
    {
        $this->dateDerniereRelance = $d;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(User $u): static
    {
        $this->user = $u;
        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }
    public function setEntreprise(Entreprise $e): static
    {
        $this->entreprise = $e;
        return $this;
    }

    public function getStatut(): ?Statut
    {
        return $this->statut;
    }
    public function setStatut(Statut $s): static
    {
        $this->statut = $s;
        return $this;
    }

    public function getRelances(): Collection
    {
        return $this->relances;
    }

    public function getStatutReponse(): string
    {
        return $this->statutReponse;
    }

    public function setStatutReponse(string $statutReponse): static
    {
        $validStatuts = [
            'attente',     // sans réponse
            'echanges',    // en discussion
            'entretien',   // au moins un entretien
            'negative',    // refus
            'positive',    // retour positif
            'annule'       // entretien annulé (dernier supprimé)
        ];
        if (!in_array($statutReponse, $validStatuts, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Statut "%s" invalide. Valeurs autorisées: %s',
                    $statutReponse,
                    implode(', ', $validStatuts)
                )
            );
        }

        $this->statutReponse = $statutReponse;

        return $this;
    }

    public function getEntretiens(): Collection
    {
        return $this->entretiens;
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
            if ($relance->getCandidature() === $this) {
                $relance->setCandidature(null);
            }
        }
        return $this;
    }

    public function addEntretien(Entretien $entretien): static
    {
        if (!$this->entretiens->contains($entretien)) {
            $this->entretiens->add($entretien);
            $entretien->setCandidature($this);
        }
        return $this;
    }

    public function removeEntretien(Entretien $entretien): static
    {
        if ($this->entretiens->removeElement($entretien)) {
            if ($entretien->getCandidature() === $this) {
                $entretien->setCandidature(null);
            }
        }
        return $this;
    }


}
