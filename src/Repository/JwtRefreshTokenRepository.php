<?php

namespace App\Repository;

use App\Entity\JwtRefreshToken;
use Doctrine\ORM\EntityRepository;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenRepositoryInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;

/**
 * @extends EntityRepository<RefreshToken>
 * @implements RefreshTokenRepositoryInterface<RefreshToken>
 */
class JwtRefreshTokenRepository extends EntityRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @param \DateTimeInterface|null $datetime
     *
     * @return JwtRefreshToken[]
     */
    public function findInvalid($datetime = null)
    {
        $datetime = (null === $datetime) ? new \DateTime() : $datetime;

        return $this->createQueryBuilder('u')
            ->where('u.valid < :datetime')
            ->setParameter(':datetime', $datetime)
            ->getQuery()
            ->getResult();
    }
}
