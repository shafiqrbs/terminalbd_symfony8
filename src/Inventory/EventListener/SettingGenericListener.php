<?php

namespace App\Inventory\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Inventory\Entity\CodeAwareEntity;

class SettingGenericListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {

        $entity = $args->getObject();
        // perhaps you only want to act on some "Purchase" entity
        if ($entity instanceof CodeAwareEntity) {

            $lastCode = $this->getLastCode($args,$entity);
            $entity->setCode((int)$lastCode+1);

        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $entity
     * @return int|mixed
     */
    public function getLastCode(LifecycleEventArgs $args, $entity)
    {

        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository(\App\Inventory\Entity\ItemColor::class)->createQueryBuilder('s');
        $qb
            ->select('MAX(s.code)')
            ->where('s.inventoryConfig = :inventory')
            ->setParameter('inventory', 2);
            $lastCode = $qb->getQuery()->getSingleScalarResult();

        if (empty($lastCode)) {
            return 0;
        }

        return $lastCode;
    }


}