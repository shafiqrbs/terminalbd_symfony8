<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 4/9/15
 * Time: 5:05 PM
 */

namespace App\Tool\EventListener;


use App\Tool\Service\SmsGateWay;

abstract class BaseSmsAwareListener {

    /**
     * @var SmsGateWay
     */
    protected $gateway;

    public function  __construct(SmsGateWay $gateway)
    {
        $this->gateway = $gateway;
    }

}