<?php

namespace App\Entity;

use App\Repository\RelanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RelanceRepository::class)]
class Relance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateRelance = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\ManyToOne(inversedBy: 'relances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Candidature $candidature = null;

    /**
     * @var Collection<int, MotCle>
     */
    #[ORM\ManyToMany(targetEntity: MotCle::class, inversedBy: 'relances')]
    private Collection $motsCles;

    public function __construct()
    {
        $this->motsCles = new ArrayCollection();
    }

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
