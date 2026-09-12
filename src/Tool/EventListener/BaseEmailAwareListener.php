<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 4/9/15
 * Time: 5:05 PM
 */

namespace App\Tool\EventListener;


use App\Tool\Service\EasyMailer;

abstract class BaseEmailAwareListener {

    /**
     * @var EasyMailer
     */
    protected $easymailer;

    public function  __construct(EasyMailer $easymailer)
    {
        $this->easymailer = $easymailer;
    }

}