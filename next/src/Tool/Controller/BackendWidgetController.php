<?php

namespace App\Tool\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
class BackendWidgetController extends AbstractController
{

    public function topBarAction($slug='')
    {
        $em = $this->getDoctrine()->getManager();
        return $this->render('@SettingTool/Widget/common.html.twig');

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {

            $globalOption = $this->getUser()->getGlobalOption();
            return $this->render('@SettingTool/Widget/admin.html.twig', [
                'globalOption' => $globalOption
            ]);

        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_DOMAIN')) {

            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
           // $todaySalesOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->todaySalesOverview($inventory);
            //$accountHeads = $em->getRepository(\App\Accounting\Entity\Transaction::class)->specificParentAccountHead($inventory,23);
            return $this->render('@SettingTool/Widget/common.html.twig', [
               // 'todaySalesOverview'      => $todaySalesOverview,
                //'accountHeads'      => $accountHeads,
            ]);

        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_DOMAIN_INVENTORY') ) {

            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $todaySalesOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->todaySalesOverview($inventory);
            $accountHeads = $em->getRepository(\App\Accounting\Entity\Transaction::class)->specificParentAccountHead($inventory,23);
            return $this->render('@SettingTool/Widget/admin.html.twig', [
                'todaySalesOverview'      => $todaySalesOverview,
                'accountHeads'      => $accountHeads,
            ]);

        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_DOMAIN_INVENTORY_SALES')) {

            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $todaySalesOverview = $em->getRepository(\App\Inventory\Entity\Sales::class)->todaySalesOverview($inventory);
            $accountHeads = $em->getRepository(\App\Accounting\Entity\Transaction::class)->specificParentAccountHead($inventory,23);
            return $this->render('@SettingTool/Widget/admin.html.twig', [
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
