<?php
namespace JustCommunication\AuthBundle\Model;
use Symfony\Component\Security\Core\User\UserInterface;
/**
 * Описание авторизованного пользователя, что имеет и что может
 */
class AuthUser implements UserInterface{
    /**
     * @var int
     */
    private $id=0;

    /**
     * @var string
     */
    private $email ='';
        /**
     * @var string
     */
    private $phone;
    /**
     * @var array
     */
    private $roles;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return AuthUser
     */
    public function setId(int $id): AuthUser
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return AuthUser
     */
    public function setEmail(string $email): AuthUser
    {
        $this->email = $email;
        return $this;
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
     * @return AuthUser
     */
    public function setPhone(string $phone): AuthUser
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     * @return AuthUser
     */
    public function setRoles(array $roles): AuthUser
    {
        $this->roles = $roles;
        return $this;
    }


    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        // TODO: Implement getUserIdentifier() method.
    }
}
