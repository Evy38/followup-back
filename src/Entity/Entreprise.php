<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente une entreprise à laquelle un utilisateur a postulé.
 *
 * Les entreprises sont créées à la volée lors de la soumission d'une candidature
 * via {@see \App\Controller\Api\CandidatureFromOfferController}.
 * Elles ne sont pas liées à un utilisateur spécifique : une même entreprise peut
 * apparaître dans les candidatures de plusieurs utilisateurs.
 */
#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
class Entreprise
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['candidature:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['candidature:read'])]
    private string $nom;

    /**
     * @var Collection<int, Candidature>
     */
    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'entreprise')]
    private Collection $candidatures;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
    }

    // ---------------- Getters / Setters ----------------

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }
}
