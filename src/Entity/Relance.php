<?php

namespace App\Entity;

use App\Repository\RelanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Patch;
use App\State\RelanceUpdateProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente une action de relance planifiée pour une candidature.
 *
 * Trois relances sont automatiquement générées à la création d'une candidature
 * (J+7, J+14, J+21) par {@see \App\Service\RelanceService}.
 *
 * Le champ `rang` (1, 2, 3) identifie l'ordre de la relance dans le plan.
 * Le champ `type` porte un libellé lisible ("S+1", "S+2", "S+3").
 * Lorsqu'une relance est marquée comme faite (`faite = true`), la date de réalisation
 * est automatiquement renseignée et la `dateDerniereRelance` de la candidature est mise
 * à jour via {@see \App\State\RelanceUpdateProcessor}.
 */
#[ORM\Entity(repositoryClass: RelanceRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            security: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['relance:read']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['relance:read']]
        ),
        new Put(
            processor: RelanceUpdateProcessor::class,
            security: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['relance:write']]
        ),
        new Patch(
            processor: RelanceUpdateProcessor::class,
            security: "object.getCandidature().getUser() == user",
            denormalizationContext: ['groups' => ['relance:write']],
            inputFormats: ['json' => ['application/merge-patch+json', 'application/json']]
        ),
        new Delete(
            security: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')"
        )
    ],
    normalizationContext: ['groups' => ['relance:read']],
    denormalizationContext: ['groups' => ['relance:write']]
)]
class Relance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['relance:read', 'candidature:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Groups(['relance:read', 'relance:write', 'candidature:read'])]
    private ?\DateTimeImmutable $dateRelance = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['relance:read', 'relance:write', 'candidature:read'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['relance:read', 'relance:write', 'candidature:read'])]
    private ?string $contenu = null;

    #[ORM\ManyToOne(inversedBy: 'relances')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['relance:read'])]
    private ?Candidature $candidature = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(10)]
    #[Groups(['relance:read', 'relance:write', 'candidature:read'])]
    private int $rang = 1;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['relance:write', 'relance:read', 'candidature:read'])]
    private bool $faite = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['relance:write', 'relance:read', 'candidature:read'])]
    private ?\DateTimeImmutable $dateRealisation = null;

    public function __construct()
    {
        $this->motsCles = new ArrayCollection();
    }

    // --- GETTERS / SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateRelance(): ?\DateTimeImmutable
    {
        return $this->dateRelance;
    }

    public function setDateRelance(\DateTimeImmutable $dateRelance): static
    {
        $this->dateRelance = $dateRelance;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getCandidature(): ?Candidature
    {
        return $this->candidature;
    }

    public function setCandidature(?Candidature $candidature): static
    {
        $this->candidature = $candidature;
        return $this;
    }

    public function getRang(): int
    {
        return $this->rang;
    }
    public function setRang(int $rang): static
    {
        $this->rang = $rang;
        return $this;
    }

    public function isFaite(): bool
    {
        return $this->faite;
    }
    public function setFaite(bool $faite): static
    {
        $this->faite = $faite;

        if ($faite && $this->dateRealisation === null) {
            $this->dateRealisation = new \DateTimeImmutable();
        }

        if (!$faite) {
            $this->dateRealisation = null;
        }

        return $this;
    }

    public function getDateRealisation(): ?\DateTimeImmutable
    {
        return $this->dateRealisation;
    }
    public function setDateRealisation(?\DateTimeImmutable $d): static
    {
        $this->dateRealisation = $d;
        return $this;
    }
}
