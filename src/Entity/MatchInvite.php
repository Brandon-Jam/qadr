<?php

namespace App\Entity;

use App\Repository\MatchInviteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchInviteRepository::class)]
class MatchInvite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'matchInvitesSent')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentParticipant $challenger = null;

    #[ORM\ManyToOne(inversedBy: 'matchInvitesReceived')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentParticipant $opponent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending | accepted | refused

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // -------------------------
    //        GETTERS
    // -------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenger(): ?TournamentParticipant
    {
        return $this->challenger;
    }

    public function getOpponent(): ?TournamentParticipant
    {
        return $this->opponent;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // -------------------------
    //        SETTERS
    // -------------------------

    public function setChallenger(TournamentParticipant $challenger): self
    {
        $this->challenger = $challenger;
        return $this;
    }

    public function setOpponent(TournamentParticipant $opponent): self
    {
        $this->opponent = $opponent;
        return $this;
    }

    public function setTournament(Tournament $tournament): self
    {
        $this->tournament = $tournament;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
