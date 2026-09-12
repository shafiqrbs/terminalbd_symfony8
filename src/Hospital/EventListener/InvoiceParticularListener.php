<?php

namespace App\Hospital\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use DateTime;
use App\Hospital\Entity\InvoiceParticular;

class InvoiceParticularListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        // perhaps you only want to act on some "Sales" entity
        if ($entity instanceof InvoiceParticular) {

            $datetime = new DateTime("now");
            $lastCode = $this->getLastCode($args, $datetime, $entity);
            $entity->setCode($lastCode+1);
            $entity->setReportCode(sprintf("%s%s", $datetime->format('dmy'), str_pad($entity->getCode(),3, '0', STR_PAD_LEFT)));

        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $datetime
     * @param $entity
     * @return int|mixed
     */
    public function getLastCode(LifecycleEventArgs $args, $datetime, $entity)
    {
        $today_startdatetime = $datetime->format('Y-m-d 00:00:00');
        $today_enddatetime = $datetime->format('Y-m-d 23:59:59');


        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository(\App\Hospital\Entity\InvoiceParticular::class)->createQueryBuilder('ip');

        $qb
            ->select('MAX(ip.code)')
            ->join('s.hmsInvoice','s')
            ->where('s.hospitalConfig = :hospital')
            ->andWhere('s.updated >= :today_startdatetime')
            ->andWhere('s.updated <= :today_enddatetime')
            ->setParameter('hospital', $entity->getInvoice()->getHospitalConfig())
            ->setParameter('today_startdatetime', $today_startdatetime)
            ->setParameter('today_enddatetime', $today_enddatetime);
        $lastCode = $qb->getQuery()->getSingleScalarResult();

        if (empty($lastCode)) {
            return 0;
        }

        return $lastCode;
    }
}