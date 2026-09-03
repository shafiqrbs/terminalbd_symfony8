<?php

namespace App\Medicine\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Medicine\Entity\MedicineStock;

class MedicineStockListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        // perhaps you only want to act on some "Purchase" entity
        if ($entity instanceof MedicineStock) {
            $lastCode = $this->getLastCode($args,$entity);
            $entity->setCode((int)$lastCode+1);
            $entity->setSku(sprintf("%s",str_pad($entity->getCode(),4, '0', STR_PAD_LEFT)));
            if(empty($entity->getBarcode())){
                $entity->setBarcode(sprintf("%s",str_pad($entity->getCode(),6, '0', STR_PAD_LEFT)));
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $entity
     * @return int|mixed
     */
    public function getLastCode(LifecycleEventArgs $args,MedicineStock $entity)
    {

        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository(\App\Medicine\Entity\MedicineStock::class)->createQueryBuilder('s');
        $qb
            ->select('MAX(s.code)')
            ->where('s.medicineConfig = :config')
            ->setParameter('config', $entity->getMedicineConfig());
            $lastCode = $qb->getQuery()->getSingleScalarResult();

        if (empty($lastCode)) {
            return 0;
        }
        return $lastCode;
    }

}