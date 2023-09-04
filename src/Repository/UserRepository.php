<?php

namespace JustCommunication\AuthBundle\Repository;

use JustCommunication\AuthBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{

    public function __construct(ManagerRegistry $registry, private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }


    /**
     * Стараемся придерживаться единого формата записи номеров телефонов
     * @param string $phone
     * @return string
     */
    public function phoneFormat(string $phone):string{
        if (str_starts_with($phone, '+')){
            $phone = str_replace('+', '', $phone);
        }

        if (str_starts_with($phone, '8') && strlen($phone)==11){
            $phone = '7'.substr($phone, 1);
        }

        $phone= '+'.$phone;
        return $phone;
    }

    public function findByPhone(string $phone):?User{

        return $this->findOneBy(['phone'=>$this->phoneFormat($phone)]);
    }

    public function newUser(string $phone, string $email, string $name, string $pass):User{

        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $pass);

        $user->setDatein(new \DateTime())->setDateen(new \DateTime()) // первая дата входа совпадает с датой регистрации
            ->setPhone($phone)
            ->setEmail($email)
            ->setName($name)
            ->setPassword($hashedPassword)
            ->setRoles(['ROLE_USER'])
            ;
        $this->_em->persist($user);
        $this->_em->flush();
        return $user;
    }


    /**
     * Выборка списка записей по id
     * @param array $ids
     * @return float|int|mixed|string
     */
    public function findByIds(array $ids){
        $arr = $this->_em->createQuery(
            'SELECT u FROM '.$this->getClassName().' u 
                WHERE u.id in (:ids)
            ')
            ->setParameter('ids', $ids)
            ->getResult();

        return FuncHelper::indexBy($arr, fn($item)=>$item->getId());
    }


}
