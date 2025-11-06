<?php

namespace App\Entity;

use App\Repository\TournamentParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Tournament;

#[ORM\Entity(repositoryClass: TournamentParticipantRepository::class)]
class TournamentParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $confirmed = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

 #[ORM\ManyToOne(inversedBy: 'tournamentParticipants')]
private ?User $user = null;

#[ORM\ManyToOne(inversedBy: 'participants')]
private ?Tournament $tournament = null;
    

    /**
     * @var Collection<int, TournamentParticipantCard>
     */
   #[ORM\OneToMany(mappedBy: 'participant', targetEntity: TournamentParticipantCard::class, cascade: ['persist', 'remove'])]
    private Collection $tournamentParticipantCards;

    public function __construct()
    {
        $this->tournamentParticipantCards = new ArrayCollection();
        $this->cards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isConfirmed(): ?bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): static
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getUser(): ?User
{
    return $this->user;
}

public function setUser(?User $user): static
{
    $this->user = $user;
    return $this;
}

public function getTournament(): ?Tournament
{
    return $this->tournament;
}

public function setTournament(?Tournament $tournament): static
{
    $this->tournament = $tournament;
    return $this;
}

    /**
     * @return Collection<int, TournamentParticipantCard>
     */
    public function getTournamentParticipantCards(): Collection
    {
        return $this->tournamentParticipantCards;
    }

    public function addTournamentParticipantCard(TournamentParticipantCard $tournamentParticipantCard): static
    {
        if (!$this->tournamentParticipantCards->contains($tournamentParticipantCard)) {
            $this->tournamentParticipantCards->add($tournamentParticipantCard);
            $tournamentParticipantCard->setTournamentParticipant($this);
        }

        return $this;
    }

    public function removeTournamentParticipantCard(TournamentParticipantCard $tournamentParticipantCard): static
    {
        if ($this->tournamentParticipantCards->removeElement($tournamentParticipantCard)) {
            // set the owning side to null (unless already changed)
            if ($tournamentParticipantCard->getTournamentParticipant() === $this) {
                $tournamentParticipantCard->setTournamentParticipant(null);
            }
        }

        return $this;
    }

    #[ORM\Column(type: 'integer', options: ['default' => 10])]
private int $credits = 10;

public function getCredits(): int
{
    return $this->credits;
}

public function setCredits(int $credits): self
{
    $this->credits = $credits;
    return $this;
}

#[ORM\OneToMany(mappedBy: 'participant', targetEntity: TournamentParticipantCard::class)]
private Collection $cards;



public function getCards(): Collection
{
    return $this->cards;
}
}
