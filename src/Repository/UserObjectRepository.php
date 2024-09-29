<?php

namespace App\Repository;

use App\Entity\UserObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserObject>
 *
 * @method UserObject|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserObject|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserObject[]    findAll()
 * @method UserObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserObjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserObject::class);
    }

//    /**
//     * @return UserObject[] Returns an array of UserObject objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserObject
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
