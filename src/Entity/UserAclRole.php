<?php

namespace JustCommunication\AuthBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Справочная таблица возможных ролей пользователей
 */
#[ORM\Table(name: "user_acl_role")]
#[ORM\Entity()]
class UserAclRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column()]
    private int $id;

    #[ORM\Column(length: 255, unique: true)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $title;

    //----------------------------------------------------------------------------------------------------------------//

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return UserAclRole
     */
    public function setId(int $id): UserAclRole
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     * @return UserAclRole
     */
    public function setPassword(?string $password): UserAclRole
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return UserAclRole
     */
    public function setName(string $name): UserAclRole
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return UserAclRole
     */
    public function setTitle(string $title): UserAclRole
    {
        $this->title = $title;
        return $this;
    }

}
