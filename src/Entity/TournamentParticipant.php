<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Tournament;
use App\Entity\MatchInvite;
use App\Entity\TournamentParticipantCard;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\TournamentParticipantRepository;

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

    #[ORM\Column(type: 'integer')]
    private int $hp = 10;

    // ----- MATCH INVITES -----
    #[ORM\OneToMany(mappedBy: 'challenger', targetEntity: MatchInvite::class, cascade: ['remove'])]
    private Collection $matchInvitesSent;

    #[ORM\OneToMany(mappedBy: 'opponent', targetEntity: MatchInvite::class, cascade: ['remove'])]
    private Collection $matchInvitesReceived;

    // ----- CARDS -----
    #[ORM\OneToMany(mappedBy: 'participant', targetEntity: TournamentParticipantCard::class, cascade: ['persist', 'remove'])]
    private Collection $tournamentParticipantCards;

    #[ORM\OneToMany(mappedBy: 'participant', targetEntity: TournamentParticipantCard::class)]
    private Collection $cards;

    #[ORM\Column(type: 'integer', options: ['default' => 10])]
    private int $credits = 10;

    #[ORM\Column(type: 'integer')]
    private int $creditsEarned = 0;

    #[ORM\Column(type: 'integer')]
    private int $creditsSpent = 0;

    public function __construct()
    {
        $this->tournamentParticipantCards = new ArrayCollection();
        $this->cards = new ArrayCollection();
        $this->matchInvitesSent = new ArrayCollection();
        $this->matchInvitesReceived = new ArrayCollection();
    }

    // -----------------------------------
    // BASIC GETTERS / SETTERS
    // -----------------------------------

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

    // -----------------------------------
    // CREDITS SYSTEM
    // -----------------------------------

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): self
    {
        $this->credits = $credits;
        return $this;
    }

    public function getCreditsEarned(): int
    {
        return $this->creditsEarned;
    }

    public function setCreditsEarned(int $creditsEarned): self
    {
        $this->creditsEarned = $creditsEarned;
        return $this;
    }

    public function getCreditsSpent(): int
    {
        return $this->creditsSpent;
    }

    public function setCreditsSpent(int $creditsSpent): self
    {
        $this->creditsSpent = $creditsSpent;
        return $this;
    }

    public function getHp(): int
{
    return $this->hp;
}

public function setHp(int $hp): self
{
    $this->hp = $hp;
    return $this;
}

    // -----------------------------------
    // CARDS
    // -----------------------------------

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
            if ($tournamentParticipantCard->getTournamentParticipant() === $this) {
                $tournamentParticipantCard->setTournamentParticipant(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, TournamentParticipantCard>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    // -----------------------------------
    // MATCH INVITES
    // -----------------------------------

    /**
     * @return Collection<int, MatchInvite>
     */
    public function getMatchInvitesSent(): Collection
    {
        return $this->matchInvitesSent;
    }

    public function addMatchInvitesSent(MatchInvite $invite): self
    {
        if (!$this->matchInvitesSent->contains($invite)) {
            $this->matchInvitesSent->add($invite);
            $invite->setChallenger($this);
        }
        return $this;
    }

    public function removeMatchInvitesSent(MatchInvite $invite): self
    {
        if ($this->matchInvitesSent->removeElement($invite)) {
            if ($invite->getChallenger() === $this) {
                $invite->setChallenger(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, MatchInvite>
     */
    public function getMatchInvitesReceived(): Collection
    {
        return $this->matchInvitesReceived;
    }

    public function addMatchInvitesReceived(MatchInvite $invite): self
    {
        if (!$this->matchInvitesReceived->contains($invite)) {
            $this->matchInvitesReceived->add($invite);
            $invite->setOpponent($this);
        }
        return $this;
    }

    public function removeMatchInvitesReceived(MatchInvite $invite): self
    {
        if ($this->matchInvitesReceived->removeElement($invite)) {
            if ($invite->getOpponent() === $this) {
                $invite->setOpponent(null);
            }
        }
        return $this;
    }
}
