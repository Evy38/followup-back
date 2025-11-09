<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
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

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['entreprise:read']]),
        new GetCollection(normalizationContext: ['groups' => ['entreprise:read']])
    ],
    security: "is_granted('ROLE_USER')"
)]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['entreprise:read', 'candidature:read', 'candidature:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['entreprise:read', 'entreprise:write', 'candidature:read', 'candidature:write'])]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['entreprise:read', 'entreprise:write'])]
    private ?string $secteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['entreprise:read', 'entreprise:write'])]
    private ?string $siteWeb = null;

    /**
     * @var Collection<int, Candidature>
     */
    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'entreprise')]
    private Collection $candidatures;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
    }

    // --- GETTERS / SETTERS ---

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getSecteur(): ?string { return $this->secteur; }
    public function setSecteur(?string $secteur): static { $this->secteur = $secteur; return $this; }
    public function getSiteWeb(): ?string { return $this->siteWeb; }
    public function setSiteWeb(?string $siteWeb): static { $this->siteWeb = $siteWeb; return $this; }

    public function getCandidatures(): Collection { return $this->candidatures; }
}
