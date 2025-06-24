<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\MatchCardPlayRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchCardPlayRepository::class)]
class MatchCardPlay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?TournamentMatch $match = null;

    #[ORM\ManyToOne]
    private ?TournamentParticipant $player = null;

    #[ORM\ManyToOne]
    private ?Card $card = null;

    #[ORM\Column]
    private \DateTimeImmutable $playedAt;

        #[ORM\ManyToOne]
#[ORM\JoinColumn(nullable: false)]
private ?User $usedBy = null;

    #[ORM\Column(nullable: true)]
    private ?string $effectApplied = null;

    
#[ORM\Column(type: 'datetime')]
private ?\DateTimeInterface $usedAt = null;


    public function __construct()
    {
        $this->playedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatch(): ?TournamentMatch
    {
        return $this->match;
    }

    public function setMatch(?TournamentMatch $match): self
    {
        $this->match = $match;
        return $this;
    }

    public function getPlayer(): ?TournamentParticipant
    {
        return $this->player;
    }

    public function setPlayer(?TournamentParticipant $player): self
    {
        $this->player = $player;
        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): self
    {
        $this->card = $card;
        return $this;
    }

    public function getPlayedAt(): \DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function setPlayedAt(\DateTimeImmutable $playedAt): self
    {
        $this->playedAt = $playedAt;
        return $this;
    }

    public function getEffectApplied(): ?string
    {
        return $this->effectApplied;
    }

    public function setEffectApplied(?string $effectApplied): self
    {
        $this->effectApplied = $effectApplied;
        return $this;
    }

public function getUsedBy(): ?User
{
    return $this->usedBy;
}

public function setUsedBy(?User $user): self
{
    $this->usedBy = $user;
    return $this;
}

public function getUsedAt(): ?\DateTimeInterface
{
    return $this->usedAt;
}

public function setUsedAt(\DateTimeInterface $usedAt): self
{
    $this->usedAt = $usedAt;
    return $this;
}
}
