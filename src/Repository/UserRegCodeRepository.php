<?php


namespace JustCommunication\AuthBundle\Repository;
use JustCommunication\AuthBundle\Entity\User;
use JustCommunication\AuthBundle\Entity\UserRegCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


class UserRegCodeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct($registry, UserRegCode::class);
    }

    /**
     * Выборка текущего активного кода для регистрации в системе
     * если не уникален берет последний
     * @param string $phone
     * @return ?UserRegCode
     */
    public function getActiveCodeByPhone(string $phone):?UserRegCode{

        $date = new \DateTime(date("Y-m-d H:i:s", date("U")-$_ENV["SECURITY_REG_CODE_TIMEOUT"]));
        $arr = $this->_em->createQuery(
            'SELECT c FROM '.$this->getClassName().' c 
                WHERE c.phone = :phone AND c.datech > :date AND c.entry = 0
                ORDER BY c.id DESC
                ')
            //WHERE u.phone = :phone AND UNIX_TIMESTAMP(datech)+'.$_ENV["SECURITY_REG_CODE_TIMEOUT"].'>UNIX_TIMESTAMP(now())
            ->setParameter('phone', $phone)
            ->setParameter('date', $date)
            //->getOneOrNullResult();
            ->getResult();
        // Мы не можем быть уверены что код будет только один. Достаточно сменить конфиг и под активного может попасть несколько кодов
        if (count($arr)>=1){
            reset($arr);
            return current($arr);
        }else{
            return null;
        }
    }

    /**
     * Создание нового кода для пользователя
     * @param User $user
     * @return UserRegCode
     */
    public function newCode(string $phone, $ip, array $payload=[]):UserRegCode{
        // Можно подумать над форматом кодов
        $code = rand((int)str_repeat('1', $_ENV['SECURITY_REG_CODE_LEN']), (int)str_repeat('9', $_ENV['SECURITY_REG_CODE_LEN']));
        $userAuthCode = new UserRegCode();
        $userAuthCode->setDatein(new \DateTime())->setDatech(new \DateTime())
            ->setCode($code)
            ->setPhone($phone)
            ->setPortal($_ENV['APP_NAME'])
            ->setTries(1)
            ->setIp($ip)
            ->setPayload($payload)
            ->setEntry(false);
        $this->_em->persist($userAuthCode);
        $this->_em->flush();
        return $userAuthCode;
    }

    /**
     * Продление кода (в случае если запросили снова, а он еще активен
     * @param UserRegCode $userAuthCode
     * @return UserRegCode
     */
    public function prolongCode(UserRegCode $userAuthCode, array $payload=[]){
        $userAuthCode->setTries($userAuthCode->getTries()+1)
            ->setPayload($payload)
            ->setDatech(new \DateTime());
        $this->_em->persist($userAuthCode);
        $this->_em->flush();
        return $userAuthCode;
    }

    public function useCode(UserRegCode $userAuthCode){
        $userAuthCode->setEntry(true)
            ->setDatech(new \DateTime());
        $this->_em->persist($userAuthCode);
        $this->_em->flush();
        return $userAuthCode;
    }

    function isValidCode(string $phone, int $code):?UserRegCode{
        //$this->findOneBy(['idUser'=>$idUser, 'code'=>$code, ]);

        $date = new \DateTime(date("Y-m-d H:i:s", date("U")-$_ENV["SECURITY_REG_CODE_TIMEOUT"]));
        $codeRow = $this->_em->createQuery(
            'SELECT c FROM '.$this->getClassName().' c 
                WHERE c.phone = :phone AND c.code = :code AND c.datech > :date AND c.entry = 0
                ')
            ->setParameter('phone', $phone)
            ->setParameter('code', $code)
            ->setParameter('date', $date)
            ->getOneOrNullResult();

        return $codeRow;
    }



}