<?php

namespace JustCommunication\AuthBundle\Entity;

use JustCommunication\AuthBundle\Model\NotificationCodeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "user_auth_code")]
#[ORM\Index(columns: ["phone"], name: "phone")]
#[ORM\Index(columns: ["id_user", "code", "entry"], name: "login")]
#[ORM\Index(columns: ["phone", "datech", "entry"], name: "find_exist")]
#[ORM\Entity()]
class UserAuthCode implements NotificationCodeInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column()]
    private int $id;


    #[ORM\Column(name:"id_user")]
    private int $idUser;

    #[ORM\Column(length: 20)]
    private string $phone;

    #[ORM\Column()]
    private \DateTime $datein;

    #[ORM\Column()]
    private \DateTime $datech;

    #[ORM\Column(length: 10)]
    private string $code;

    #[ORM\Column(length: 50)]
    private $portal;

    #[ORM\Column()]
    private int $tries;

    //использовался код для входа или нет
    #[ORM\Column()]
    private bool $entry;

    //------------------------------------------------------------------------------------------------------------------


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return UserAuthCode
     */
    public function setId(int $id): UserAuthCode
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getIdUser(): int
    {
        return $this->idUser;
    }

    /**
     * @param int $idUser
     * @return UserAuthCode
     */
    public function setIdUser(int $idUser): UserAuthCode
    {
        $this->idUser = $idUser;
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
     * @return UserAuthCode
     */
    public function setPhone(string $phone): UserAuthCode
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
     * @return UserAuthCode
     */
    public function setDatein(\DateTime $datein): UserAuthCode
    {
        $this->datein = $datein;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatech(): \DateTime
    {
        return $this->datech;
    }

    /**
     * @param \DateTime $datech
     * @return UserAuthCode
     */
    public function setDatech(\DateTime $datech): UserAuthCode
    {
        $this->datech = $datech;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return UserAuthCode
     */
    public function setCode(string $code): UserAuthCode
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param mixed $portal
     * @return UserAuthCode
     */
    public function setPortal($portal): UserAuthCode
    {
        $this->portal = $portal;
        return $this;
    }

    /**
     * @return int
     */
    public function getEntry(): bool|int
    {
        return $this->entry;
    }

    /**
     * @param int $entry
     * @return UserAuthCode
     */
    public function setEntry(bool|int $entry): UserAuthCode
    {
        $this->entry = $entry;
        return $this;
    }

    /**
     * @return int
     */
    public function getTries(): int
    {
        return $this->tries;
    }

    /**
     * @param int $tries
     * @return UserAuthCode
     */
    public function setTries(int $tries): UserAuthCode
    {
        $this->tries = $tries;
        return $this;
    }


    /**
     * Возвращает количество секунд через которые разрешено запрашивать код снова
     * @return int
     */
    public function getRemainTimeForRepeat(): int
    {
        $seconds_remain = ($this->datech->getTimestamp()+$_ENV['SECURITY_AUTH_CODE_DELAY'])-date('U');
        return $seconds_remain>0?$seconds_remain:0;
    }





}
