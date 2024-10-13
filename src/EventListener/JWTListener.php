<?php

namespace App\EventListener;

use App\Entity\Communication;
use App\Entity\User;
use App\Entity\UserPlateform;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTListener implements EventSubscriberInterface
{


    public function __construct() {}

    public static function getSubscribedEvents()
    {
        return [
            Events::JWT_CREATED => 'onJWTCreated',
            Events::JWT_AUTHENTICATED => 'onJWTAuthenticated',
        ];
    }

    public function onJWTCreated(JWTCreatedEvent $event,)
    {
        /** @var User $user */
        $user = $event->getUser();

        // $communication = $em->getRepository(Communication::class)->findOneBy(['client' => $user]);
        $payload = $event->getData();
        $payload['id'] = $user->getId();
        $payload['username'] = $user->getUsername();
        $payload['email'] = $user->getEmail();


        $event->setData($payload);
    }

    public function onJWTAuthenticated(JWTAuthenticatedEvent $event)
    {
        $token = $event->getToken();
        $payload = $event->getPayload();
        $token->setAttribute('id', $payload['id']);
        $token->setAttribute('username', $payload['username']);
        $token->setAttribute('email', $payload['email']);
    }
}
