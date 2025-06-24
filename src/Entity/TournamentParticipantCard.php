<?php

namespace App\Entity;

use App\Repository\TournamentParticipantCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentParticipantCardRepository::class)]
class TournamentParticipantCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

     #[ORM\ManyToOne(inversedBy: 'tournamentParticipantCards')]
#[ORM\JoinColumn(nullable: false)]
private ?TournamentParticipant $participant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Card $card = null;

    #[ORM\Column(nullable: true)]
    private ?int $quantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipant(): ?TournamentParticipant
{
    return $this->participant;
}


   public function setParticipant(?TournamentParticipant $participant): static
{
    $this->participant = $participant;
    return $this;
}

    public function getCard(): ?card
    {
        return $this->card;
    }

    public function setCard(?card $Card): static
    {
        $this->card = $Card;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
