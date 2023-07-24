<?php


namespace JustCommunication\AuthBundle\Repository;
use JustCommunication\AuthBundle\Entity\User;
use JustCommunication\AuthBundle\Entity\UserAuthCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


class UserAuthCodeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct($registry, UserAuthCode::class);
    }

    /**
     * Выборка текущего активного кода для входа в систему
     * если не уникален берет последний
     * @param string $phone
     * @return ?UserAuthCode
     */
    public function getActiveCodeByPhone(string $phone):?UserAuthCode{

        $date = new \DateTime(date("Y-m-d H:i:s", date("U")-$_ENV["SECURITY_AUTH_CODE_TIMEOUT"]));
        $arr = $this->_em->createQuery(
                'SELECT c FROM '.$this->getClassName().' c 
                WHERE c.phone = :phone AND c.datech > :date AND c.entry = 0
                ORDER BY c.id DESC
                ')
            //WHERE u.phone = :phone AND UNIX_TIMESTAMP(datech)+'.$_ENV["SECURITY_AUTH_CODE_TIMEOUT"].'>UNIX_TIMESTAMP(now())
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
     * @return UserAuthCode
     */
    public function newCode(User $user):UserAuthCode{
        // Можно подумать над форматом кодов

        //$code = rand(pow(10, $_ENV['SECURITY_AUTH_CODE_LEN']),9*pow(10, $_ENV['SECURITY_AUTH_CODE_LEN']));
        $code = rand((int)str_repeat('1', $_ENV['SECURITY_AUTH_CODE_LEN']), (int)str_repeat('9', $_ENV['SECURITY_AUTH_CODE_LEN']));
        $userAuthCode = new UserAuthCode();
        $userAuthCode->setDatein(new \DateTime())->setDatech(new \DateTime())
            ->setCode($code)
            ->setIdUser($user->getId())
            ->setPhone($user->getPhone())
            ->setPortal($_ENV['APP_NAME'])
            ->setTries(1)
            ->setEntry(false);
        $this->_em->persist($userAuthCode);
        $this->_em->flush();
        return $userAuthCode;
    }

    /**
     * Продление кода (в случае если запросили снова, а он еще активен
     * @param UserAuthCode $userAuthCode
     * @return UserAuthCode
     */
    public function prolongCode(UserAuthCode $userAuthCode){
        $userAuthCode->setTries($userAuthCode->getTries()+1)
            ->setDatech(new \DateTime());
        $this->_em->persist($userAuthCode);
        $this->_em->flush();
        return $userAuthCode;
    }

    /**
     * Запросили код, но мы его не отдали, срок не продляем, но счетчик увеличиваем
     * @param UserAuthCode $userAuthCode
     * @return UserAuthCode
     */
    public function idleTry(UserAuthCode $userAuthCode){
        $userAuthCode->setTries($userAuthCode->getTries()+1);
        $this->_em->persist($userAuthCode);
        $this->_em->flush();
        return $userAuthCode;
    }




    public function useCode(UserAuthCode $userAuthCode){
        $userAuthCode->setEntry(true)
            ->setDatech(new \DateTime());
        $this->_em->persist($userAuthCode);
        $this->_em->flush();
        return $userAuthCode;
    }

    function isValidCode(int $idUser, string $code):?UserAuthCode{
        //$this->findOneBy(['idUser'=>$idUser, 'code'=>$code, ]);

        $date = new \DateTime(date("Y-m-d H:i:s", date("U")-$_ENV["SECURITY_AUTH_CODE_TIMEOUT"]));
        $codeRow = $this->_em->createQuery(
            'SELECT c FROM '.$this->getClassName().' c 
                WHERE c.idUser = :idUser AND c.code = :code AND c.datech > :date AND c.entry = 0
                ')
            ->setParameter('idUser', $idUser)
            ->setParameter('code', $code)
            ->setParameter('date', $date)
            ->getOneOrNullResult();

        return $codeRow;
    }



}