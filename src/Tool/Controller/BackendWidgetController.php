<?php

namespace App\Tool\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
class BackendWidgetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    public function topBarAction($slug='')
    {
        $em = $this->em;
        return $this->render('Tool/Widget/common.html.twig');

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {

            $globalOption = $this->getUser()->getGlobalOption();
            return $this->render('Tool/Widget/admin.html.twig', [
                'globalOption' => $globalOption
            ]);

        }elseif ($this->isGranted('ROLE_DOMAIN')) {

            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
           // $todaySalesOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->todaySalesOverview($inventory);
            //$accountHeads = $em->getRepository(\App\Accounting\Entity\Transaction::class)->specificParentAccountHead($inventory,23);
            return $this->render('Tool/Widget/common.html.twig', [
               // 'todaySalesOverview'      => $todaySalesOverview,
                //'accountHeads'      => $accountHeads,
            ]);

        }elseif ($this->isGranted('ROLE_DOMAIN_INVENTORY') ) {

            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $todaySalesOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->todaySalesOverview($inventory);
            $accountHeads = $em->getRepository(\App\Accounting\Entity\Transaction::class)->specificParentAccountHead($inventory,23);
            return $this->render('Tool/Widget/admin.html.twig', [
                'todaySalesOverview'      => $todaySalesOverview,
                'accountHeads'      => $accountHeads,
            ]);

        }elseif ($this->isGranted('ROLE_DOMAIN_INVENTORY_SALES')) {

            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $todaySalesOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->todaySalesOverview($inventory);
            $accountHeads = $em->getRepository(\App\Accounting\Entity\Transaction::class)->specificParentAccountHead($inventory,23);
            return $this->render('Tool/Widget/admin.html.twig', [
                'todaySalesOverview'      => $todaySalesOverview,
                'accountHeads'      => $accountHeads,
            ]);

        }else{
            return $this->redirect($this->generateUrl('bindu_homepage'));
        }
    }

    public function inventory()
    {


    }

}
