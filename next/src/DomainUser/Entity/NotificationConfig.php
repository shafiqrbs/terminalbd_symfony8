<?php

namespace App\DomainUser\Entity;

use App\DomainUser\Repository\NotificationConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Tool\Entity\GlobalOption;


/**
 * NotificationConfig
 */
#[ORM\Table]
#[ORM\Entity(repositoryClass: NotificationConfigRepository::class)]
class NotificationConfig
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\OneToOne(targetEntity: GlobalOption::class, inversedBy: 'notificationConfig')]
    private $globalOption;


    /**
     * @var boolean
     */
    #[ORM\Column(name: 'onlineOrder', type: 'boolean')]
    private $onlineOrder = true;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'customerBaseOrder', type: 'boolean')]
    private $customerBaseOrder = true;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'smsActive', type: 'boolean')]
    private $smsActive = true;

    /**
      * @var string
      */
     #[ORM\Column(name: 'mobile', type: 'text', nullable: true)]
     private $mobile;

   /**
      * @var string
      */
     #[ORM\Column(name: 'paymentNotification', type: 'text', nullable: true)]
     private $paymentNotification;

      /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }



    /**
     * @return GlobalOption
     */
    public function getGlobalOption()
    {
        return $this->globalOption;
    }

    /**
     * @param GlobalOption $globalOption
     */
    public function setGlobalOption($globalOption)
    {
        $this->globalOption = $globalOption;
    }

    /**
     * @return boolean
     */
    public function getOnlineOrder()
    {
        return $this->onlineOrder;
    }

    /**
     * @param boolean $onlineOrder
     */
    public function setOnlineOrder($onlineOrder)
    {
        $this->onlineOrder = $onlineOrder;
    }

    /**
     * @return boolean
     */
    public function isCustomerBaseOrder()
    {
        return $this->customerBaseOrder;
    }

    /**
     * @param boolean $customerBaseOrder
     */
    public function setCustomerBaseOrder($customerBaseOrder)
    {
        $this->customerBaseOrder = $customerBaseOrder;
    }

    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
    }

    /**
     * @return boolean
     */
    public function getSmsActive()
    {
        return $this->smsActive;
    }

    /**
     * @param boolean $smsActive
     */
    public function setSmsActive($smsActive)
    {
        $this->smsActive = $smsActive;
    }

    /**
     * @return string
     */
    public function getPaymentNotification()
    {
        return $this->paymentNotification;
    }

    /**
     * @param string $paymentNotification
     */
    public function setPaymentNotification($paymentNotification)
    {
        $this->paymentNotification = $paymentNotification;
    }


}

