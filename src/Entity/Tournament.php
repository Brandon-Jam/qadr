<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column]
    private ?int $availableSlots = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?float $winningPrice = null;

    #[ORM\Column(type: 'integer')]
    private int $maxPendingSlots = 80; // limite pré-inscriptions

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isValidated = false;

    #[ORM\Column(type: 'boolean')]
    private bool $active = false;


    /**
     * @var Collection<int, TournamentMatch>
     */
    #[ORM\OneToMany(targetEntity: TournamentMatch::class, mappedBy: 'tournament')]
    private Collection $tournamentMatches;

    /**
     * @var Collection<int, TournamentParticipant>
     */
    #[ORM\OneToMany(targetEntity: TournamentParticipant::class, mappedBy: 'tournament')]
    private Collection $tournamentParticipants;

    /**
     * @var Collection<int, TournamentCard>
     */
    #[ORM\OneToMany(targetEntity: TournamentCard::class, mappedBy: 'tournament')]
    private Collection $tournamentCards;

    public function __construct()
    {
        $this->tournamentMatches = new ArrayCollection();
        $this->tournamentParticipants = new ArrayCollection();
        $this->tournamentCards = new ArrayCollection();
        $this->referees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getAvailableSlots(): ?int
    {
        return $this->availableSlots;
    }

    public function setAvailableSlots(int $availableSlots): static
    {
        $this->availableSlots = $availableSlots;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getWinningPrice(): ?float
    {
        return $this->winningPrice;
    }

    public function setWinningPrice(float $winningPrice): static
    {
        $this->winningPrice = $winningPrice;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }


    /**
     * @return Collection<int, TournamentMatch>
     */
    public function getTournamentMatches(): Collection
    {
        return $this->tournamentMatches;
    }

    public function addTournamentMatch(TournamentMatch $tournamentMatch): static
    {
        if (!$this->tournamentMatches->contains($tournamentMatch)) {
            $this->tournamentMatches->add($tournamentMatch);
            $tournamentMatch->setTournament($this);
        }

        return $this;
    }

    public function removeTournamentMatch(TournamentMatch $tournamentMatch): static
    {
        if ($this->tournamentMatches->removeElement($tournamentMatch)) {
            // set the owning side to null (unless already changed)
            if ($tournamentMatch->getTournament() === $this) {
                $tournamentMatch->setTournament(null);
            }
        }

        return $this;
    }

    #[ORM\OneToMany(mappedBy: 'tournament', targetEntity: TournamentParticipant::class, orphanRemoval: true)]
    private Collection $participants;



    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * @return Collection<int, TournamentParticipant>
     */
    public function getTournamentParticipants(): Collection
    {
        return $this->tournamentParticipants;
    }

    public function addTournamentParticipant(TournamentParticipant $tournamentParticipant): static
    {
        if (!$this->tournamentParticipants->contains($tournamentParticipant)) {
            $this->tournamentParticipants->add($tournamentParticipant);
            $tournamentParticipant->setTournament($this);
        }

        return $this;
    }

    public function removeTournamentParticipant(TournamentParticipant $tournamentParticipant): static
    {
        if ($this->tournamentParticipants->removeElement($tournamentParticipant)) {
            // set the owning side to null (unless already changed)
            if ($tournamentParticipant->getTournament() === $this) {
                $tournamentParticipant->setTournament(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TournamentCard>
     */
    public function getTournamentCards(): Collection
    {
        return $this->tournamentCards;
    }

    public function addTournamentCard(TournamentCard $tournamentCard): static
    {
        if (!$this->tournamentCards->contains($tournamentCard)) {
            $this->tournamentCards->add($tournamentCard);
            $tournamentCard->setTournament($this);
        }

        return $this;
    }

    public function removeTournamentCard(TournamentCard $tournamentCard): static
    {
        if ($this->tournamentCards->removeElement($tournamentCard)) {
            // set the owning side to null (unless already changed)
            if ($tournamentCard->getTournament() === $this) {
                $tournamentCard->setTournament(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: "tournament_referees")]
    #[ORM\JoinColumn(name: "tournament_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "user_id", referencedColumnName: "id")]
    private Collection $referees;




    // ✅ Getters / setters
    public function getReferees(): Collection
    {
        return $this->referees;
    }

    public function addReferee(User $referee): self
    {
        if (!$this->referees->contains($referee)) {
            $this->referees->add($referee);
        }

        return $this;
    }

    public function removeReferee(User $referee): self
    {
        $this->referees->removeElement($referee);
        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}
