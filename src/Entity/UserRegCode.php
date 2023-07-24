<?php

namespace JustCommunication\AuthBundle\Entity;
use JustCommunication\AuthBundle\Model\NotificationCodeInterface;
use JustCommunication\AuthBundle\Repository\UserRegCodeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Коды подтверждения телефона при регистрации
 */
#[ORM\Table(name: "user_reg_code")]
#[ORM\Index(columns: ["phone"], name: "phone")]
#[ORM\Index(columns: ["code", "entry"], name: "login")]
#[ORM\Index(columns: ["phone", "datech", "entry"], name: "find_exist")]
#[ORM\Entity()]
#[ORM\HasLifecycleCallbacks()]
class UserRegCode implements NotificationCodeInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column()]
    private int $id;

    #[ORM\Column()]
    private \DateTime $datein;

    #[ORM\Column()]
    private \DateTime $datech;

    #[ORM\Column(length: 20)]
    private string $phone;

    #[ORM\Column(length: 10)]
    private string $code;

    #[ORM\Column(length: 50)]
    private $portal;

    #[ORM\Column()]
    private int $tries;

    #[ORM\Column(length: 50)]
    private string $ip;

    //использовался код для регистрации или нет
    #[ORM\Column()]
    private bool $entry;

    // Полезная нагрузка содержит всю сопутсвующую информацию (имя, email и т.д.) только для того чтобы при подтверждении кода не слать ее заново
    // Однако эту информацию следует проверить при регистрации повторно
    #[ORM\Column(name:"payload", type:"json", nullable:false)]
    private array $payload = [];



    //------------------------------------------------------------------------------------------------------------------


    /** @ORM\preUpdate */
    /*
    public function updateDatech()
    {
        $this->datech = new \DateTime();
        $this->try = $this->try+1;
        //dd($this->try);
    }*/

    /** @ORM\postLoad */
    /*
    public function postLoad()
    {
        $this->datech = new \DateTime();
        $this->try = $this->try+1;
        //dd($this->try);
    }
    */

    #[ORM\PrePersist()]
    public function incrementValues()
    {
        $this->setDatein(new \DateTime());
        //dd($this->try);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return UserRegCode
     */
    public function setId(int $id): UserRegCode
    {
        $this->id = $id;
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
     * @return UserRegCode
     */
    public function setDatein(\DateTime $datein): UserRegCode
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
     * @return UserRegCode
     */
    public function setDatech(\DateTime $datech): UserRegCode
    {
        $this->datech = $datech;
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
     * @return UserRegCode
     */
    public function setPhone(string $phone): UserRegCode
    {
        $this->phone = $phone;
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
     * @return UserRegCode
     */
    public function setCode(string $code): UserRegCode
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
     * @return UserRegCode
     */
    public function setPortal($portal): UserRegCode
    {
        $this->portal = $portal;
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
     * @return UserRegCode
     */
    public function setTries(int $tries): UserRegCode
    {
        $this->tries = $tries;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return UserRegCode
     */
    public function setIp(string $ip): UserRegCode
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEntry(): bool
    {
        return $this->entry;
    }

    /**
     * @param bool $entry
     * @return UserRegCode
     */
    public function setEntry(bool $entry): UserRegCode
    {
        $this->entry = $entry;
        return $this;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     * @return UserRegCode
     */
    public function setPayload(array $payload): UserRegCode
    {
        $this->payload = $payload;
        return $this;
    }


    /**
     * Возвращает количество секунд через которые разрешено запрашивать код снова
     * @return int
     */
    public function getRemainTimeForRepeat(): int
    {
        $seconds_remain = ($this->datech->getTimestamp()+$_ENV['SECURITY_REG_CODE_DELAY'])-date('U');
        return $seconds_remain>0?$seconds_remain:0;
    }


}
