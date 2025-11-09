<?php

namespace App\Entity;

use App\Repository\RelanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;

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
        new Post(
            securityPostDenormalize: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['relance:write']]
        ),
        new Put(
            security: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['relance:write']]
        ),
        new Delete(
            security: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')"
        )
    ]
)]
class Relance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['relance:read', 'candidature:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['relance:read', 'relance:write', 'candidature:read', 'candidature:write'])]
    private ?\DateTimeImmutable $dateRelance = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['relance:read', 'relance:write', 'candidature:read', 'candidature:write'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['relance:read', 'relance:write', 'candidature:read', 'candidature:write'])]
    private ?string $contenu = null;

    #[ORM\ManyToOne(inversedBy: 'relances')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['relance:read', 'relance:write'])]
    private ?Candidature $candidature = null;

    /**
     * @var Collection<int, MotCle>
     */
    #[ORM\ManyToMany(targetEntity: MotCle::class, inversedBy: 'relances')]
    #[Groups(['relance:read', 'relance:write', 'candidature:read', 'candidature:write'])]
    private Collection $motsCles;

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

    /**
     * @return Collection<int, MotCle>
     */
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
}
