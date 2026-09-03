<?php

namespace App\Inventory\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Inventory\Entity\ItemColor;

class ItemColorListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Purchase" entity
        if ($entity instanceof ItemColor) {

            $lastCode = $this->getLastCode($args);
            $entity->setCode((int)$lastCode+1);

        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $entity
     * @return int|mixed
     */
    public function getLastCode(LifecycleEventArgs $args)
    {

        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository(\App\Inventory\Entity\ItemColor::class)->createQueryBuilder('s');
        $qb
            ->select('MAX(s.code)')
            ->where('s.isValid = 1');
        $lastCode = $qb->getQuery()->getSingleScalarResult();
        if (empty($lastCode)) {
            return 0;
        }
        return $lastCode;
    }


}