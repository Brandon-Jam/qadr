<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', length: 50, unique: true, nullable: true)]
    private ?string $pseudo = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $xp = 0;


    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\ManyToMany(targetEntity: Tournament::class, mappedBy: "referees")]
private Collection $refereedTournaments;

    /**
     * @var Collection<int, TournamentMatch>
     */
    #[ORM\OneToMany(targetEntity: TournamentMatch::class, mappedBy: 'player1')]
    private Collection $tournamentMatches;

    /**
     * @var Collection<int, TournamentParticipant>
     */
    #[ORM\OneToMany(targetEntity: TournamentParticipant::class, mappedBy: 'user')]
    private Collection $tournament;

    public function __construct()
    {
        $this->tournamentMatches = new ArrayCollection();
        $this->tournament = new ArrayCollection();
        $this->tournamentParticipations = new ArrayCollection();
        $this->refereedTournaments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }
    

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getRefereedTournaments(): Collection
{
    return $this->refereedTournaments;
}

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): self
    {
        // on évite les valeurs négatives
        $this->xp = max(0, $xp);

        return $this;
    }

    public function addXp(int $amount): self
    {
        $this->xp = max(0, $this->xp + $amount);

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getPseudo(): ?string
{
    return $this->pseudo;
}

public function setPseudo(?string $pseudo): self
{
    $this->pseudo = $pseudo;
    return $this;
}

public function getLevel(): int
{
    $xp = $this->xp;

    // 10 niveaux
    if ($xp < 100) return 1;
    if ($xp < 250) return 2;
    if ($xp < 500) return 3;
    if ($xp < 800) return 4;
    if ($xp < 1200) return 5;
    if ($xp < 1700) return 6;
    if ($xp < 2300) return 7;
    if ($xp < 3000) return 8;
    if ($xp < 3800) return 9;

    return 10;
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
            $tournamentMatch->setPlayer1($this);
        }

        return $this;
    }

    public function removeTournamentMatch(TournamentMatch $tournamentMatch): static
    {
        if ($this->tournamentMatches->removeElement($tournamentMatch)) {
            // set the owning side to null (unless already changed)
            if ($tournamentMatch->getPlayer1() === $this) {
                $tournamentMatch->setPlayer1(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TournamentParticipant>
     */
    public function getTournament(): Collection
    {
        return $this->tournament;
    }

    public function addTournament(TournamentParticipant $tournament): static
    {
        if (!$this->tournament->contains($tournament)) {
            $this->tournament->add($tournament);
            $tournament->setUser($this);
        }

        return $this;
    }

    public function removeTournament(TournamentParticipant $tournament): static
    {
        if ($this->tournament->removeElement($tournament)) {
            // set the owning side to null (unless already changed)
            if ($tournament->getUser() === $this) {
                $tournament->setUser(null);
            }
        }

        return $this;
    }

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TournamentParticipant::class)]
private Collection $tournamentParticipations;

public function getTournamentParticipations(): Collection
{
    return $this->tournamentParticipations;
}
}
