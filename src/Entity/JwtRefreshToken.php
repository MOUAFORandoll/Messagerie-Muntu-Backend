<?php

namespace App\Entity;

use App\Traits\SoftCreateUpdateDeleteTrait;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\JwtRefreshTokenRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

/**
 * This class extends Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken to have another table name.
 *
 * */
#[ORM\Entity(repositoryClass: JwtRefreshTokenRepository::class)]
class JwtRefreshToken extends BaseRefreshToken
{
    #[ORM\Column(type: "datetime", nullable: true)]
    private $dateExpireToken;

    public function getDateExpireToken(): ?DateTimeInterface
    {
        return $this->dateExpireToken;
    }

    public function setDateExpireToken(DateTimeInterface $dateExpireToken): self
    {
        $this->dateExpireToken = $dateExpireToken;
        return $this;
    }

    /**
     * Creates a new model instance based on the provided details.
     */
    public static function createForUserWithTtl(string $refreshToken, UserInterface $user, int $ttl): RefreshTokenInterface
    {
        $valid = new \DateTime();
        $valid->modify('+' . $ttl . ' seconds');

        $model = new static();
        $model->setRefreshToken($refreshToken);
        $model->setUsername(method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : $user->getNameUser());
        $model->setValid($valid);
        $dateExpireToken = new \DateTime();
        $dateExpireToken->modify("+60 minutes");
        $model->setDateExpireToken($dateExpireToken);
        return $model;
    }

    /**
     * @return string Refresh Token
     */
    public function __toString()
    {
        return $this->serializerRefreshToken() ?: '';
    }
}
