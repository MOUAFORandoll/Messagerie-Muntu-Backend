<?php

namespace App\EventListener;

use App\Entity\Communication;
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
        /** @var UserPlateform $user */
        $user = $event->getUser();

        // $communication = $em->getRepository(Communication::class)->findOneBy(['client' => $user]);
        $payload = $event->getData();
        $payload['id'] = $user->getId();
        $payload['nom'] = $user->getNom();
        $payload['prenom'] = $user->getPrenom();
        $payload['email'] = $user->getEmail();
        $payload['phone'] = $user->getPhone();
        $payload['keySecret'] = $user->getKeySecret();
        $payload['typeUser'] = $user->getTypeParticipant()->getId();
        $payload['codeParrainnage'] = $user->getCodeParrainage();

        $event->setData($payload);
    }

    public function onJWTAuthenticated(JWTAuthenticatedEvent $event)
    {
        $token = $event->getToken();
        $payload = $event->getPayload();
        $token->setAttribute('id', $payload['id']);
        $token->setAttribute('typeUser', $payload['typeUser']);
        $token->setAttribute('nom', $payload['nom']);
        $token->setAttribute('prenom', $payload['prenom']);
        $token->setAttribute('keySecret', $payload['keySecret']);
        $token->setAttribute('codeParrainnage', $payload['codeParrainnage']);
        $token->setAttribute('email', $payload['email']);
        $token->setAttribute('phone', $payload['phone']);
    }
}
