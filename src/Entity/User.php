<?php

namespace JustCommunication\AuthBundle\Entity;

use JustCommunication\AuthBundle\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: "user")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'bigint')]
    private int $id;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private ?string $email=null;

    #[ORM\Column(length: 100)]
    private ?string $name=null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column(name: 'password', type: 'string', length: 255, nullable: false)]
    private string $password;

    #[ORM\Column(name:"roles", type:"json", nullable:false)]
    private array $roles = [];

    /**
     * дата регистрации
     */
    #[ORM\Column(name: 'datein', type: 'datetime', nullable: false)]
    private \DateTime $datein;

    /**
     * дата входа
     */
    #[ORM\Column(name: 'dateen', type: 'datetime', nullable: false)]
    private \DateTime $dateen;

    //--------------------------------------------------------------------------------------


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): User
    {
        $this->id = $id;
        return $this;
    }


    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_substr($email, 0, 100);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return User
     */
    public function setName(?string $name): User
    {
        $this->name = mb_substr($name, 0, 100);
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

    public function getUserName(): string
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

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

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

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return User
     */
    public function setPhone(string $phone): User
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatein(): \DateTime
    {
        return $this->datein;
    }

    /**
     * @param \DateTime $datein
     * @return User
     */
    public function setDatein(\DateTime $datein): User
    {
        $this->datein = $datein;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateen(): \DateTime
    {
        return $this->dateen;
    }

    /**
     * @param \DateTime $dateen
     * @return User
     */
    public function setDateen(\DateTime $dateen): User
    {
        $this->dateen = $dateen;
        return $this;
    }


}
