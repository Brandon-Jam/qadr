<?php

namespace App\Entity;

use App\Repository\TournamentMatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentMatchRepository::class)]
class TournamentMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $score1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $score2 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?string $round = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isValidated = false;

    #[ORM\ManyToOne(inversedBy: 'tournamentMatches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(inversedBy: 'matchesAsPlayer1')]
#[ORM\JoinColumn(nullable: false)]
private ?TournamentParticipant $player1 = null;

#[ORM\ManyToOne(inversedBy: 'matchesAsPlayer2')]
#[ORM\JoinColumn(nullable: false)]
private ?TournamentParticipant $player2 = null;

    #[ORM\ManyToOne(inversedBy: 'tournamentMatches')]
    private ?User $winner = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScore1(): ?int
    {
        return $this->score1;
    }

    public function setScore1(?int $score1): static
    {
        $this->score1 = $score1;

        return $this;
    }

    public function getScore2(): ?int
    {
        return $this->score2;
    }

    public function setScore2(?int $score2): static
    {
        $this->score2 = $score2;

        return $this;
    }

    public function getRound(): ?string
    {
        return $this->round;
    }

    public function setRound(string $round): static
    {
        $this->round = $round;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
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

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): static
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getPlayer1(): ?TournamentParticipant
{
    return $this->player1;
}

public function setPlayer1(?TournamentParticipant $player1): self
{
    $this->player1 = $player1;
    return $this;
}

    public function getPlayer2(): ?TournamentParticipant
{
    return $this->player2;
}

public function setPlayer2(?TournamentParticipant $player2): self
{
    $this->player2 = $player2;
    return $this;
}

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(?User $winner): static
    {
        $this->winner = $winner;

        return $this;
    }

    public function isValidated(): bool
{
    return $this->isValidated;
}

public function setIsValidated(bool $isValidated): self
{
    $this->isValidated = $isValidated;

    return $this;
}

}
