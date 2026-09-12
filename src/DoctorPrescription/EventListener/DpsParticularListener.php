<?php

namespace App\DoctorPrescription\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\DoctorPrescription\Entity\DpsParticular;

class DpsParticularListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {

        $entity = $args->getObject();
        if ($entity instanceof DpsParticular) {
            $lastCode = $this->getLastCode($args,$entity);
            $entity->setCode((int)$lastCode + 1);
            $entity->setParticularCode(sprintf("%s",str_pad($entity->getCode(),3, '0', STR_PAD_LEFT)));
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $entity
     * @return int|mixed
     */
    public function getLastCode(LifecycleEventArgs $args, $entity)
    {

        $class = $entity::class;
        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository($class)->createQueryBuilder('s');
        $qb
            ->select('MAX(s.code)')
            ->where('s.dpsConfig = :dps')
            ->setParameter('dps', $entity->getDpsConfig())
            ->andWhere('s.service = :service')
            ->setParameter('service', $entity->getService());
        $lastCode = $qb->getQuery()->getSingleScalarResult();

        if (empty($lastCode)) {
            return 0;
        }
        return $lastCode;

    }


}