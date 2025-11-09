<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
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

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            security: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['reponse:read']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['reponse:read']]
        ),
        new Post(
            securityPostDenormalize: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['reponse:write']]
        ),
        new Put(
            security: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['reponse:write']]
        ),
        new Delete(
            security: "object.getCandidature().getUser() == user or is_granted('ROLE_ADMIN')"
        )
    ]
)]
class Reponse
{
 #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reponse:read', 'candidature:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['reponse:read', 'reponse:write', 'candidature:read', 'candidature:write'])]
    private ?\DateTimeImmutable $dateReponse = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['reponse:read', 'reponse:write', 'candidature:read', 'candidature:write'])]
    private ?string $canal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['reponse:read', 'reponse:write', 'candidature:read', 'candidature:write'])]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['reponse:read', 'reponse:write'])]
    private ?Candidature $candidature = null;

    /**
     * @var Collection<int, MotCle>
     */
    #[ORM\ManyToMany(targetEntity: MotCle::class, inversedBy: 'reponses')]
    #[Groups(['reponse:read', 'reponse:write', 'candidature:read', 'candidature:write'])]
    private Collection $motsCles;

    public function __construct()
    {
        $this->motsCles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReponse(): ?\DateTimeImmutable
    {
        return $this->dateReponse;
    }

    public function setDateReponse(\DateTimeImmutable $dateReponse): static
    {
        $this->dateReponse = $dateReponse;
        return $this;
    }

    public function getCanal(): ?string
    {
        return $this->canal;
    }

    public function setCanal(?string $canal): static
    {
        $this->canal = $canal;
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
            $motCle->addReponse($this);
        }
        return $this;
    }

    public function removeMotCle(MotCle $motCle): static
    {
        if ($this->motsCles->removeElement($motCle)) {
            $motCle->removeReponse($this);
        }
        return $this;
    }
}
