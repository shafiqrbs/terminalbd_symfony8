<?php

namespace Appstore\Bundle\BusinessBundle\Controller;

use Appstore\Bundle\BusinessBundle\Entity\Courier;
use Appstore\Bundle\BusinessBundle\Form\CourierType;
use Appstore\Bundle\MedicineBundle\Entity\MedicineAndroidProcess;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

/**
 * Courier controller.
 *u
 */
class AndroidSalesController extends Controller
{
    /**
     * @Secure(roles="ROLE_DOMAIN,ROLE_MEDICINE_SALES,ROLE_MEDICINE_MANAGER");
     */

    public function androidSalesAction()
    {
        $conf = $this->getUser()->getGlobalOption()->getMedicineConfig()->getId();
        $entities = $this->getDoctrine()->getRepository('MedicineBundle:MedicineAndroidProcess')->getAndroidSalesList($conf,"sales");
        $pagination = $this->paginate($entities);
        $sales = $this->getDoctrine()->getRepository('MedicineBundle:MedicineSales')->findAndroidDeviceSales($pagination);
        return $this->render('MedicineBundle:Sales:salesAndroid.html.twig', array(
            'entities' => $pagination,
            'sales' => $sales,
        ));
    }

    /**
     * @Secure(roles="ROLE_DOMAIN,ROLE_MEDICINE_SALES,ROLE_MEDICINE_MANAGER");
     */

    public function androidSalesProcessAction($device)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $this->getDoctrine()->getRepository('MedicineBundle:MedicineSales')->androidDeviceSalesProcess($device);
        exit;
    }


    /**
     * @Secure(roles="ROLE_DOMAIN,ROLE_MEDICINE,ROLE_MEDICINE_SALES,ROLE_MEDICINE_MANAGER");
     */

    public function insertGroupApiSalesImportAction(MedicineAndroidProcess $android)
    {
        $msg = "invalid";
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();

        $removeSalesItem = $em->createQuery("DELETE MedicineBundle:MedicineSalesItem e WHERE e.androidProcess= {$android->getId()}");
        if(!empty($removeSalesItem)){
            $removeSalesItem->execute();
        }
        $removeSales = $em->createQuery("DELETE MedicineBundle:MedicineSales e WHERE e.androidProcess= {$android->getId()}");
        if(!empty($removeSales)){
            $removeSales->execute();
        }
        //  $this->androidProcessSymfony($android);
        $status = $this->androidProcessMysql($android);

        return new Response($status);


    }


    public function androidProcessSymfony($android){
        $em = $this->getDoctrine()->getManager();

        $this->getDoctrine()->getRepository('MedicineBundle:MedicineSales')->insertApiSales($config->getGlobalOption(),$android);
        $salses = $this->getDoctrine()->getRepository("MedicineBundle:MedicineSales")->findBy(array('androidProcess' => $android));
        foreach ($salses as $sales){
            if($sales->getProcess() == "Device"){
                $sales->setProcess('Done');
                $sales->setUpdated($sales->getCreated());
                $sales->setApprovedBy($this->getUser());
                $em->flush();
                $this->getDoctrine()->getRepository('AccountingBundle:AccountSales')->insertMedicineAccountInvoice($sales);
                $msg = "valid";
            }
        }

    }

    public function androidProcessMysql($android){
        $config = $this->getUser()->getGlobalOption()->getMedicineConfig();
        $em = $this->getDoctrine()->getManager();
        $status = $this->getDoctrine()->getRepository('BusinessBundle:BusinessAndroidProcess')->insertApiSalesManual($config->getGlobalOption(),$android);
        if($status > 0 ){
            $android->setStatus(true);
            $em->persist($android);
            $em->flush();
            return 'success';
        }else{
            return 'failed';
        }
    }


}
