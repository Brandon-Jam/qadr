<?php

namespace App\Entity;

use App\Repository\TournamentCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentCardRepository::class)]
class TournamentCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tournamentCards')]
    private ?tournament $tournament = null;

     #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Card $card = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): ?tournament
    {
        return $this->tournament;
    }

    public function setTournament(?tournament $tournament): static
    {
        $this->tournament = $tournament;

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
}
