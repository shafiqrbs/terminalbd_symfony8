<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 10/9/15
 * Time: 8:05 AM
 */

namespace App\Tool\Repository;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\InvoiceSmsEmail;
use App\Tool\Entity\SmsSenderTotal;


class SmsSenderTotalRepository extends EntityRepository {

    public function updateSmsBundle(InvoiceSmsEmail $entity)
    {
        $sms = $this->getEntityManager()->getRepository(\App\Tool\Entity\SmsSenderTotal::class)->findOneBy(['globalOption'=> $entity->getGlobalOption()]);
        if(empty($sms)){
            $sms = new SmsSenderTotal();
            $sms->setGlobalOption($entity->getGlobalOption());
            $sms->setPurchase($entity->getSmsQuantity());
            $sms->setRemaining($entity->getSmsQuantity());
        }else{
            $purchase = $sms->getPurchase();
            $sms->setPurchase($purchase + $entity->getSmsQuantity());
            $sms->setRemaining($purchase + $entity->getSmsQuantity() - $sms->getSending());
        }
        $this->getEntityManager()->persist($sms);
        $this->getEntityManager()->flush();

        $sendSms = $this->getEntityManager()->getRepository(\App\Tool\Entity\SmsSender::class)->findBy(['globalOption'=> $entity->getGlobalOption()]);
        if(!empty($sendSms)){
            foreach($sendSms as $send){
                $this->getEntityManager()->remove($send);
                $this->getEntityManager()->flush();
            }

        }


    }
} 