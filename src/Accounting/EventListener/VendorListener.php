<?php

namespace App\Accounting\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Accounting\Entity\AccountVendor;

class VendorListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        // perhaps you only want to act on some "Sales" entity
        if ($entity instanceof AccountVendor) {
            $lastCode = $this->getLastCode($args, $entity);
            $entity->setCode($lastCode+1);
            $entity->setVendorCode(sprintf("%s", str_pad($entity->getCode(),3, '0', STR_PAD_LEFT)));
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $datetime
     * @param $entity
     * @return int|mixed
     */
    public function getLastCode(LifecycleEventArgs $args, $entity)
    {
        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository(\App\Accounting\Entity\AccountVendor::class)->createQueryBuilder('s');

        $qb
            ->select('MAX(s.code)')
            ->where('s.globalOption = :globalOption')
            ->setParameter('globalOption', $entity->getGlobalOption());
        $lastCode = $qb->getQuery()->getSingleScalarResult();

        if (empty($lastCode)) {
            return 0;
        }
        return $lastCode;
    }
}