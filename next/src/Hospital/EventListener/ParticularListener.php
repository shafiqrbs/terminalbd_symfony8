<?php

namespace App\Hospital\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Hospital\Entity\Particular;

class ParticularListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {

        $entity = $args->getObject();

        if ($entity instanceof Particular) {
          //  echo $entity->getId();
            $lastCode = $this->getLastCode($args, $entity);
            $entity->setCode((int)$lastCode + 1);
            $entity->setParticularCode(sprintf("%s%s", $entity->getService()->getCode(), str_pad($entity->getCode(), 5, '0', STR_PAD_LEFT)));
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
            ->where('s.hospitalConfig = :hospital')
            ->setParameter('hospital', $entity->getHospitalConfig())
            ->andWhere('s.service = :service')
            ->setParameter('service', $entity->getService());
            $lastCode = $qb->getQuery()->getSingleScalarResult();

        if (empty($lastCode)) {
            return 0;
        }

        return $lastCode;
    }


}