<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use App\State\EntretienProcessor;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(
            security: "object.getCandidature().getUser() == user"
        ),
        new Post(
            processor: EntretienProcessor::class,
            securityPostDenormalize: "object.getCandidature().getUser() == user"
        ),
        new Patch(
            processor: EntretienProcessor::class,
            security: "object.getCandidature().getUser() == user"
        ),
        new Delete(
            processor: EntretienProcessor::class,
            security: "object.getCandidature().getUser() == user"
        ),

    ],
    normalizationContext: ['groups' => ['entretien:read']],
    denormalizationContext: ['groups' => ['entretien:write']]
)]
class Entretien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['entretien:read', 'candidature:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['entretien:read', 'entretien:write', 'candidature:read'])]
    private \DateTimeInterface $dateEntretien;

    #[ORM\Column(type: 'time')]
    #[Groups(['entretien:read', 'entretien:write', 'candidature:read'])]
    private \DateTimeInterface $heureEntretien;

    #[ORM\Column(length: 20)]
    #[Groups(['entretien:read', 'entretien:write', 'candidature:read'])]
    private string $statut = 'prevu'; // prevu | passe

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['entretien:read', 'entretien:write', 'candidature:read'])]
    private ?string $resultat = null; // positive | negative | attente

    #[ORM\ManyToOne(inversedBy: 'entretiens')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['entretien:read', 'entretien:write'])]
    private ?Candidature $candidature = null;

    // ---------------- Getters / Setters ----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEntretien(): \DateTimeInterface
    {
        return $this->dateEntretien;
    }

    public function setDateEntretien(\DateTimeInterface $dateEntretien): self
    {
        $this->dateEntretien = $dateEntretien;
        return $this;
    }

    public function getHeureEntretien(): \DateTimeInterface
    {
        return $this->heureEntretien;
    }

    public function setHeureEntretien(\DateTimeInterface $heureEntretien): self
    {
        $this->heureEntretien = $heureEntretien;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function setResultat(?string $resultat): self
    {
        $this->resultat = $resultat;
        return $this;
    }

    public function getCandidature(): ?Candidature
    {
        return $this->candidature;
    }

    public function setCandidature(Candidature $candidature): self
    {
        $this->candidature = $candidature;
        return $this;
    }
}
