<?php

namespace App\Medicine\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Medicine\Entity\MedicinePurchaseItem;

class MedicinePurchaseItemListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        // perhaps you only want to act on some "Purchase" entity
        if ($entity instanceof MedicinePurchaseItem) {
            $lastCode = $this->getLastCode($args,$entity);
            $entity->setCode((int)$lastCode+1);
            $barcode = $entity->getMedicineStock()->getSku().'-'.$entity->getCode();
            $entity->setBarcode($barcode);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $entity
     * @return int|mixed
     */
    public function getLastCode(LifecycleEventArgs $args,MedicinePurchaseItem $entity)
    {

        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository(\App\Medicine\Entity\MedicinePurchaseItem::class)->createQueryBuilder('s');
        $qb
            ->select('MAX(s.code)')
            ->where('s.medicineStock = :medicineStock')
            ->setParameter('medicineStock', $entity->getMedicineStock());
            $lastCode = $qb->getQuery()->getSingleScalarResult();

        if (empty($lastCode)) {
            return 0;
        }
        return $lastCode;
    }

}