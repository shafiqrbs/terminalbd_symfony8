<?php

namespace App\Accounting\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Accounting\Entity\AccountHead;

class AccountHeadListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof AccountHead) {
            $lastCode = $this->getLastCode($args, $entity);
            $entity->setCode($lastCode+1);
            $entity->setCode(sprintf("%s%s", $entity->getParent()->getCode(),str_pad($entity->getCode(),2, '0', STR_PAD_LEFT)));
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $datetime
     * @param $entity
     * @return int|mixed
     */
    public function getLastCode(LifecycleEventArgs $args, AccountHead $entity)
    {
        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository(\App\Accounting\Entity\AccountHead::class)->createQueryBuilder('s');
        $qb
            ->select('COUNT(s.code)')
            ->where('s.globalOption = :globalOption')
            ->setParameter('globalOption', $entity->getGlobalOption()->getId())
            ->andWhere('s.parent = :parent')
            ->setParameter('parent', $entity->getParent()->getId());
        $lastCode = $qb->getQuery()->getSingleScalarResult();
        if (empty($lastCode)) {
            return 0;
        }
        return $lastCode;
    }
}