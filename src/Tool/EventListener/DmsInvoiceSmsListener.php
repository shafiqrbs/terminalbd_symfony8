<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 4:51 PM
 */

namespace App\Tool\EventListener;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use App\Tool\Event\DmsInvoiceSmsEvent;
use App\Tool\Event\DmsTreatmentPlanSmsEvent;
use App\Tool\Service\SmsGateWay;


class DmsInvoiceSmsListener extends BaseSmsAwareListener
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $gateway;

    public function __construct(private readonly ManagerRegistry $doctrine, SmsGateWay $gateway)
    {
        $this->gateway = $gateway;
        $this->em = $this->doctrine->getManager();
    }

    public function sendSms(DmsInvoiceSmsEvent $event)
    {
        /**
         * @var DmsInvoiceSmsEvent $event
         */

        $invoice = $event->getDmsInvoice();
        $patientMobile = "+88".$invoice->getCustomer()->getMobile();
        //$patientMobile = "+8801915109006"; //.$invoice->getCustomer()->getMobile();
        $orgName = $invoice->getDmsConfig()->getGlobalOption()->getName();
        $msg = $orgName .'\nDear '.$invoice->getCustomer()->getName().', We are always committed to provide quality service. Thank you for being with us.';
        if (!empty($patientMobile)) {
            $status = $this->gateway->send($msg,$patientMobile);
            $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertDmsInvoiceSenderSms($invoice, $status);
        }


    }

    public function sendPlanConfirmSms(DmsTreatmentPlanSmsEvent $event)
    {
        /**
         * @var DmsTreatmentPlanSmsEvent $event
         */
        $schedule = $event->getDmsTreatmentPlan();
        $invoice = $event->getDmsTreatmentPlan()->getDmsInvoice();
        $patientMobile = "+88".$invoice->getCustomer()->getMobile();
        $orgName = $invoice->getDmsConfig()->getGlobalOption()->getName();
        $orgMobile = $invoice->getDmsConfig()->getGlobalOption()->getMobile();
        $msg = $orgName.'\nDear '.$invoice->getCustomer()->getName().', You have an appointment at ' . $schedule->getAppointmentTime() .' on '. $schedule->getAppointmentDate().'. Pls confirm you will be here in time. Hotline-'.$orgMobile;

        if (!empty($patientMobile)) {
            echo $status = $this->gateway->send($msg,$patientMobile);
            $this->em->getRepository(\App\Tool\Entity\SmsSender::class)->insertDmsInvoiceTreatmentNotificationSenderSms($schedule,$msg, $status);
        }

    }

}