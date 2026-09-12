<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\Tool\Event;

use Symfony\Contracts\EventDispatcher\Event;
class OtpSmsEvent extends Event
{
    public function __construct(protected $config, protected $mobile, protected $password)
    {
    }

    /* @return  GlobalOption  */

    public function getConfig()
    {
        return $this->config;
    }

    public function getMobile()
    {
        return $this->mobile;
    }

    public function getPassword()
    {
        return $this->password;
    }


}