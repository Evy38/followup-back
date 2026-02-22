<?php

namespace App\Entity;

use App\Enum\ResultatEntretien;
use App\Enum\StatutEntretien;
use App\Repository\EntretienRepository;
use App\State\EntretienProcessor;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
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
    #[Assert\NotNull]
    private \DateTimeInterface $dateEntretien;

    #[ORM\Column(type: 'time', nullable: true)]
    #[Groups(['entretien:read', 'entretien:write', 'candidature:read'])]
    #[Assert\NotNull]
    private \DateTimeInterface $heureEntretien;

    #[ORM\Column(enumType: StatutEntretien::class)]
    #[Groups(['entretien:read', 'entretien:write', 'candidature:read'])]
    private StatutEntretien $statut = StatutEntretien::PREVU;

    #[ORM\Column(enumType: ResultatEntretien::class, nullable: true)]
    #[Groups(['entretien:read', 'entretien:write', 'candidature:read'])]
    private ?ResultatEntretien $resultat = null;

    #[ORM\ManyToOne(inversedBy: 'entretiens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
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

    public function getStatut(): StatutEntretien
    {
        return $this->statut;
    }

    public function setStatut(StatutEntretien $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getResultat(): ?ResultatEntretien
    {
        return $this->resultat;
    }

    public function setResultat(?ResultatEntretien $resultat): self
    {
        $this->resultat = $resultat;
        return $this;
    }

    public function getCandidature(): ?Candidature
    {
        return $this->candidature;
    }

    public function setCandidature(?Candidature $candidature): self
    {
        $this->candidature = $candidature;
        return $this;
    }
}