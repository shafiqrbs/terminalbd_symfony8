<?php

namespace App\Restaurant\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function indexAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $option = $user->getGlobalOption();
        $data = $_REQUEST;
        $datetime = new DateTime("now");
        $data['startDate'] = $datetime->format('Y-m-d');
        $data['endDate'] = $datetime->format('Y-m-d');
        $income = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->reportMedicineIncome($this->getUser(),$data);
        $user = $this->getUser();
        $salesCashOverview                  = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user,$data,['restaurant']);
        $purchaseCashOverview               = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($user,$data);
        $transactionMethods                 = [1];
        $transactionCashOverview            = $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->cashOverview( $this->getUser(),$transactionMethods,$data);
        $expenditureOverview                = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user,$data);
        $salesOverview                      = $this->em->getRepository(\App\Restaurant\Entity\Invoice::class)->findWithSalesOverview($this->getUser(),$data);
        $salesTransactionOverview           = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->todaySalesOverview($user,$data,'true');
        $previousSalesTransactionOverview   = $em->getRepository(\App\Restaurant\Entity\Invoice::class)->todaySalesOverview($user,$data,'false');

        return $this->render('Restaurant/Default/dashboard.html.twig', [
            'config'                            => $option->getRestaurantConfig(),
            'salesOverview'                     => $salesOverview,
            'salesTransactionOverview'          => $salesTransactionOverview,
            'previousSalesTransactionOverview'  => $previousSalesTransactionOverview,
            'option'                            => $user->getGlobalOption() ,
            'transactionCashOverview'           => $transactionCashOverview,
            'expenditureOverview'               => $expenditureOverview ,
            'salesCashOverview'                 => $salesCashOverview ,
            'purchaseCashOverview'              => $purchaseCashOverview ,
            'searchForm'                        => $data ,
        ]);

    }

}
