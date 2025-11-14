<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\TournamentParticipant;
use App\Repository\TournamentMatchRepository;

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
    private ?int $round = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startTime = null;

   #[ORM\Column(length: 20)]
private string $phase = 'cards'; // 'cards', 'battle', 'validated'

#[ORM\Column(type: 'boolean')]
private bool $player1Ready = false;

#[ORM\Column(type: 'boolean')]
private bool $player2Ready = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isValidated = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isFinished = false;

    #[ORM\ManyToOne(inversedBy: 'tournamentMatches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(inversedBy: 'matchesAsPlayer1')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentParticipant $player1 = null;

    #[ORM\ManyToOne(inversedBy: 'matchesAsPlayer2')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentParticipant $player2 = null;

    #[ORM\ManyToOne(targetEntity: TournamentParticipant::class)]
    private ?TournamentParticipant $winner = null;

    #[ORM\ManyToOne(targetEntity: TournamentParticipant::class)]
    private ?TournamentParticipant $loser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScore1(): ?int
    {
        return $this->score1;
    }

    public function setScore1(?int $score1): self
    {
        $this->score1 = $score1;

        return $this;
    }

    public function getScore2(): ?int
    {
        return $this->score2;
    }

    public function setScore2(?int $score2): self
    {
        $this->score2 = $score2;

        return $this;
    }

    public function getRound(): ?int
    {
        return $this->round;
    }

    public function setRound(?int $round): self
    {
        $this->round = $round;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

   

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function isFinished(): bool
    {
        return $this->isFinished;
    }

    public function setIsFinished(bool $isFinished): self
    {
        $this->isFinished = $isFinished;

        return $this;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): self
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

    public function getWinner(): ?TournamentParticipant
    {
        return $this->winner;
    }

    public function setWinner(?TournamentParticipant $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    public function getLoser(): ?TournamentParticipant
    {
        return $this->loser;
    }

    public function setLoser(?TournamentParticipant $loser): self
    {
        $this->loser = $loser;

        return $this;
    }

    public function getPhase(): string
{
    return $this->phase;
}

public function setPhase(string $phase): self
{
    $this->phase = $phase;
    return $this;
}

public function isPlayer1Ready(): bool
{
    return $this->player1Ready;
}

public function setPlayer1Ready(bool $ready): self
{
    $this->player1Ready = $ready;
    return $this;
}

public function isPlayer2Ready(): bool
{
    return $this->player2Ready;
}

public function setPlayer2Ready(bool $ready): self
{
    $this->player2Ready = $ready;
    return $this;
}
}
