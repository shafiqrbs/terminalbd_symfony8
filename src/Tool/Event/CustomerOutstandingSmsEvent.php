<?php
/**
 * Created by PhpStorm.
 * User: dhaka
 * Date: 8/19/14
 * Time: 5:24 PM
 */

namespace App\Tool\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\DomainUser\Entity\Customer;


class CustomerOutstandingSmsEvent extends Event
{

	/** @var Customer  */
	protected $customer;


	public function __construct(Customer $customer)
	{
		$this->customer = $customer;
	}

	/**
	 * @return Customer
	 */
	public function getCustomer()
	{
		return $this->customer;
	}


}