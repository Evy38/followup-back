<?php

namespace App\Entity;

use App\Enum\StatutReponse;
use App\Repository\CandidatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Représente une candidature à un poste d'emploi.
 *
 * Une candidature est liée à un User et une Entreprise.
 * Elle regroupe les Relances planifiées et les Entretiens associés.
 * Le champ `statutReponse` (enum StatutReponse) suit l'avancement de la réponse
 * du recruteur et est synchronisé automatiquement par CandidatureStatutSyncService
 * lors de la création/modification/suppression d'un entretien.
 *
 * @see \App\Service\CandidatureStatutSyncService
 * @see \App\State\EntretienProcessor
 */
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
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['candidature:read'])]
    private ?Uuid $id = null;

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
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['candidature:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['candidature:read'])]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(
        mappedBy: 'candidature',
        targetEntity: Relance::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[Groups(['candidature:read'])]
    private Collection $relances;

    #[ORM\Column(enumType: StatutReponse::class)]
    #[Groups(['candidature:read'])]
    private StatutReponse $statutReponse = StatutReponse::ATTENTE;

    #[ORM\OneToMany(
        mappedBy: 'candidature',
        targetEntity: Entretien::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[Groups(['candidature:read'])]
    private Collection $entretiens;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['candidature:read'])]
    private ?\DateTimeImmutable $archivedAt = null;

    public function __construct()
    {
        $this->relances = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
    }

    // ---------------- Getters / Setters ----------------

    public function getId(): ?Uuid
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

    public function setUser(?User $user): self
    {
        $this->user = $user;
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

    public function getRelances(): Collection
    {
        return $this->relances;
    }

    public function getStatutReponse(): StatutReponse
    {
        return $this->statutReponse;
    }

    public function setStatutReponse(StatutReponse $statutReponse): static
    {
        $this->statutReponse = $statutReponse;
        return $this;
    }

    public function getEntretiens(): Collection
    {
        return $this->entretiens;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): static
    {
        $this->archivedAt = $archivedAt;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
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