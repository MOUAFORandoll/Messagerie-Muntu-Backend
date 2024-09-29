<?php

namespace App\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use DateTime;
use Psr\Log\LoggerInterface;

class SoftUpdateDeleteSubscriber implements EventSubscriber
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate,
            Events::preRemove,
        ];
    }
    
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        $now = new DateTime();
        
        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt($now);
            $entityManager->persist($entity);
            $entityManager->flush();
            $this->logger->info("Entity updated: ", ['entity' => $entity]);
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        $now = new DateTime();
        
        if (method_exists($entity, 'setDeletedAt')) {
            $entity->setDeletedAt($now);
            $entityManager->persist($entity);
            $entityManager->flush();
            $this->logger->info("Entity marked as deleted: ", ['entity' => $entity]);
        }
    }
}
