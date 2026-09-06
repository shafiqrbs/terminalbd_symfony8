<?php

namespace App\Accounting\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Accounting\Entity\AccountVendor;

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function indexAction($name)
    {
        return $this->render('Accounting/Default/index.html.twig', ['name' => $name]);
    }

	public function vendorMigrationAction()
	{
		set_time_limit(0);
		ignore_user_abort(true);

		$em = $this->em;
		$entities1 = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->findAll();
		foreach ($entities1 as $vendor ){

			$entity = new AccountVendor();
			$entity->setGlobalOption($vendor->getMedicineConfig()->getGlobalOption());
			$entity->setCompanyName($vendor->getCompanyName());
			$entity->setName($vendor->getName());
			$entity->setVendorCode($vendor->getVendorCode());
			$entity->setMobile($vendor->getMobile());
			$entity->setEmail($vendor->getEmail());
			$entity->setAddress($vendor->getAddress());
			$entity->setMode($vendor->getMode());
			$entity->setOldId($vendor->getId());
			$entity->setModule('medicine');
			$em->persist($entity);
			$em->flush();
		}


		$em = $this->em;
		$entities2 = $this->em->getRepository(\App\Inventory\Entity\Vendor::class)->findAll();
		foreach ($entities2 as $vendor ){

			$entity = new AccountVendor();
			$entity->setGlobalOption($vendor->getInventoryConfig()->getGlobalOption());
			$entity->setCompanyName($vendor->getCompanyName());
			$entity->setName($vendor->getName());
			$entity->setVendorCode($vendor->getVendorCode());
			$entity->setMobile($vendor->getMobile());
			$entity->setEmail($vendor->getEmail());
			$entity->setAddress($vendor->getAddress());
			$entity->setOldId($vendor->getId());
			$entity->setModule('inventory');
			$em->persist($entity);
			$em->flush();
		}


		$em = $this->em;
		$entities3 = $this->em->getRepository(\App\Hospital\Entity\HmsVendor::class)->findAll();
		foreach ($entities3 as $vendor ){

			$entity = new AccountVendor();
			$entity->setGlobalOption($vendor->getHospitalConfig()->getGlobalOption());
			$entity->setCompanyName($vendor->getCompanyName());
			$entity->setName($vendor->getName());
			$entity->setVendorCode($vendor->getHmsVendorCode());
			$entity->setMobile($vendor->getMobile());
			$entity->setEmail($vendor->getEmail());
			$entity->setAddress($vendor->getAddress());
			$entity->setMode($vendor->getMode());
			$entity->setOldId($vendor->getId());
			$entity->setModule('hospital');
			$em->persist($entity);
			$em->flush();
		}


		$entities5 = $this->em->getRepository(\App\Inventory\Entity\Vendor::class)->findAll();
		foreach ($entities5 as $vendor ){

			$entity = new AccountVendor();
			$entity->setGlobalOption($vendor->getRestaurantConfig()->getGlobalOption());
			$entity->setCompanyName($vendor->getCompanyName());
			$entity->setName($vendor->getName());
			$entity->setVendorCode($vendor->getVendorCode());
			$entity->setMobile($vendor->getMobile());
			$entity->setEmail($vendor->getEmail());
			$entity->setAddress($vendor->getAddress());
			$entity->setMode($vendor->getMode());
			$entity->setOldId($vendor->getId());
			$entity->setModule('restaurant');
			$em->persist($entity);
			$em->flush();
		}

		exit;
	}

	public function purchaseMigrationAction()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$em = $this->em;
		$data = $_REQUEST;
		$globalOption = $this->getUser()->getGlobalOption();
		$entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findAll();
		
		/* @var $entity AccountPurchase */

		foreach ($entities as $entity){

			if(!empty($entity->getVendor())){
				$entity->setCompanyName($entity->getVendor()->getCompanyName());
				$vendor  = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findOneBy(['globalOption' =>$entity->getGlobalOption(),'oldId'=>$entity->getVendor()->getId()]);
				$entity->setAccountVendor($vendor);
				if(!empty($entity->getPurchase())){
					$entity->setGrn($entity->getPurchase()->getGrn());
				}
			}elseif(!empty($entity->getHmsVendor())){
				$entity->setCompanyName($entity->getHmsVendor()->getCompanyName());
				$vendor  = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findOneBy(['globalOption' =>$entity->getGlobalOption(),'oldId'=>$entity->getHmsVendor()->getId()]);
				$entity->setAccountVendor($vendor);
				if(!empty($entity->getHmsPurchase())){
					$entity->setGrn($entity->getMedicinePurchase()->getGrn());
				}
			}elseif(!empty($entity->getDmsVendor())){
				$entity->setCompanyName($entity->getDmsVendor()->getCompanyName());
				$vendor  = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findOneBy(['globalOption' =>$entity->getGlobalOption(),'oldId'=>$entity->getDmsVendor()->getId()]);
				$entity->setAccountVendor($vendor);
				if(!empty($entity->getHmsPurchase())){
					$entity->setGrn($entity->getDmsPurchase()->getGrn());
				}
			}elseif(!empty($entity->getRestaurantVendor())){
				$entity->setCompanyName($entity->getRestaurantVendor()->getCompanyName());
				if(!empty($entity->getDmsPurchase())){
					$entity->setGrn($entity->getDmsPurchase()->getGrn());
				}
			}elseif(!empty($entity->getMedicineVendor())){
				$entity->setCompanyName($entity->getMedicineVendor()->getCompanyName());
				$vendor  = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->findOneBy(['globalOption' =>$entity->getGlobalOption(),'oldId'=>$entity->getMedicineVendor()->getId()]);
				$entity->setAccountVendor($vendor);
				if(!empty($entity->getMedicinePurchase())){
					$entity->setGrn($entity->getMedicinePurchase()->getGrn());
				}
			}elseif(!empty($entity->getAccountVendor())){
				$entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
				if(!empty($entity->getBusinessPurchase())){
					$entity->setGrn($entity->getBusinessPurchase()->getGrn());
				}
				if(!empty($entity->getHotelPurchase())){
					$entity->setGrn($entity->getHotelPurchase()->getGrn());
				}
			}
			$em->flush();
		}
		exit;

	}


	public function salesMigrationAction()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$em = $this->em;
		$data = $_REQUEST;
		$globalOption = $this->getUser()->getGlobalOption();
		$entities = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->findAll();

		/* @var $entity AccountSales */

		foreach ($entities as $entity){

			if (!empty($entity->getSales())){
				$entity->setSourceInvoice($entity->getSales()->getInvoice());
				$entity->setProcessHead('inventory');
			}elseif (!empty($entity->getBusinessInvoice())){
				$entity->setSourceInvoice($entity->getBusinessInvoice()->getInvoice());
				$entity->setProcessHead('business');
			}elseif (!empty($entity->getHotelInvoice())){
				$entity->setSourceInvoice($entity->getHotelInvoice()->getInvoice());
				$entity->setProcessHead('hotel');
			}elseif (!empty($entity->getDmsInvoices())){
				$entity->setSourceInvoice($entity->getDmsInvoices()->getInvoice());
				$entity->setProcessHead('dental');
			}elseif (!empty($entity->getHmsInvoices())){
				$entity->setSourceInvoice($entity->getHmsInvoices()->getInvoice());
				$entity->setProcessHead('diagnostic');
			}elseif (!empty($entity->getDpsInvoice())){
				$entity->setSourceInvoice($entity->getDpsInvoice()->getInvoice());
				$entity->setProcessHead('prescription');
			}elseif (!empty($entity->getRestaurantInvoice())){
				$entity->setSourceInvoice($entity->getRestaurantInvoice()->getInvoice());
				$entity->setProcessHead('restaurant');
			}elseif (!empty($entity->getMedicineSales())){
				$entity->setSourceInvoice($entity->getMedicineSales()->getInvoice());
				$entity->setProcessHead('medicine');
			}
			$em->flush();

		}
		exit;


	}

}
