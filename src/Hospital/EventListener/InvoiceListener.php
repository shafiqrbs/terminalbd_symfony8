<?php

namespace App\Hospital\EventListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use DateTime;
use App\Hospital\Entity\Invoice;

class InvoiceListener
{
    public function prePersist(PrePersistEventArgs $args)
    {
        $this->createCode($args);
    }

    public function createCode(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        // perhaps you only want to act on some "Sales" entity
        if ($entity instanceof Invoice) {

            $datetime = new DateTime("now");
            $lastCode = $this->getLastCode($args, $datetime, $entity);
            $entity->setCode($lastCode+1);
            $mode = $entity->getInvoiceMode();
            $mode = strtoupper(substr($mode,0,3)).'-';
            if(empty($entity->getHospitalConfig()->getInvoicePrefix())){
                $entity->setInvoice(sprintf("%s%s%s",$mode,$datetime->format('ym'), str_pad($entity->getCode(),4, '0', STR_PAD_LEFT)));
            }else{
                $entity->setInvoice(sprintf("%s%s%s%s",$mode,$entity->getHospitalConfig()->getInvoicePrefix(), $datetime->format('ym'), str_pad($entity->getCode(),4, '0', STR_PAD_LEFT)));
            }

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
        $today_startdatetime = $datetime->format('Y-m-01 00:00:00');
        $today_enddatetime = $datetime->format('Y-m-t 23:59:59');


        $entityManager = $args->getObjectManager();
        $qb = $entityManager->getRepository(\App\Hospital\Entity\Invoice::class)->createQueryBuilder('s');

        $qb
            ->select('MAX(s.code)')
            ->where('s.hospitalConfig = :hospital')
            ->andWhere('s.updated >= :today_startdatetime')
            ->andWhere('s.updated <= :today_enddatetime')
            ->setParameter('hospital', $entity->getHospitalConfig())
            ->setParameter('today_startdatetime', $today_startdatetime)
            ->setParameter('today_enddatetime', $today_enddatetime);
        $lastCode = $qb->getQuery()->getSingleScalarResult();

        if (empty($lastCode)) {
            return 0;
        }

        return $lastCode;
    }
}