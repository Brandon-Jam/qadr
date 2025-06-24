<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $effect = null;

    #[ORM\Column]
    private ?int $cost = null;

    #[ORM\Column]
    private ?int $power = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    /**
     * @var Collection<int, TournamentCard>
     */
    #[ORM\OneToMany(targetEntity: TournamentCard::class, mappedBy: 'Card')]
    private Collection $tournamentCards;

    /**
     * @var Collection<int, TournamentParticipantCard>
     */
    #[ORM\OneToMany(targetEntity: TournamentParticipantCard::class, mappedBy: 'Card')]
    private Collection $tournamentParticipantCards;

    public function __construct()
    {
        $this->tournamentCards = new ArrayCollection();
        $this->tournamentParticipantCards = new ArrayCollection();
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

    public function getEffect(): ?string
    {
        return $this->effect;
    }

    public function setEffect(string $effect): static
    {
        $this->effect = $effect;

        return $this;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(int $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getPower(): ?int
    {
        return $this->power;
    }

    public function setPower(int $power): static
    {
        $this->power = $power;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

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
            $tournamentCard->setCard($this);
        }

        return $this;
    }

    public function removeTournamentCard(TournamentCard $tournamentCard): static
    {
        if ($this->tournamentCards->removeElement($tournamentCard)) {
            // set the owning side to null (unless already changed)
            if ($tournamentCard->getCard() === $this) {
                $tournamentCard->setCard(null);
            }
        }

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
            $tournamentParticipantCard->setCard($this);
        }

        return $this;
    }

    public function removeTournamentParticipantCard(TournamentParticipantCard $tournamentParticipantCard): static
    {
        if ($this->tournamentParticipantCards->removeElement($tournamentParticipantCard)) {
            // set the owning side to null (unless already changed)
            if ($tournamentParticipantCard->getCard() === $this) {
                $tournamentParticipantCard->setCard(null);
            }
        }

        return $this;
    }
}
