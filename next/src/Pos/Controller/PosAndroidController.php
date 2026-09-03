<?php

namespace App\Pos\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;


/**
 * InvoiceController controller.
 *
 */
class PosAndroidController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    public function paginate($entities)
    {

        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    #[IsGranted('ROLE_POS_MANAGER')]

    public function indexAction()
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $conf = $this->getUser()->getGlobalOption()->getInventoryConfig()->getId();
        $entities = $this->em->getRepository(\App\Inventory\Entity\InventoryAndroidProcess::class)->getAndroidSalesList($conf,"sales");
        $pagination = $this->paginate($entities);
        $sales = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->findAndroidDeviceSales($pagination);
        return $this->render('Pos/Android/index.html.twig', [
            'globalOption' => $globalOption,
            'entities' => $pagination,
            'sales' => $sales,
        ]);
    }

    public function insertGroupApiSalesImportAction($android)
    {
        /* @var $go GlobalOption */
        $go = $this->getUser()->getGlobalOption();
        $main = $go->getMainApp()->getSlug();
        $msg = "invalid";
        switch ($main){
            case 'inventory':
                $msg = $this->inventorySalesProcess($android);
                break;
            case 'miss':
                $this->inventorySalesProcess($android);
                break;
            case 'restaurant':
                $this->inventorySalesProcess($android);
                break;
        }
        return new Response($msg);
    }

    #[IsGranted('ROLE_POS_MANAGER')]

    private function inventorySalesProcess($android){

        $msg = "invalid";
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $android = $this->em->getRepository(\App\Inventory\Entity\InventoryAndroidProcess::class)->findOneBy(['inventoryConfig'=>$config,'id'=>$android]);
        $removeSales = $em->createQuery("DELETE InventoryBundle:Sales e WHERE e.androidProcess= {$android->getId()}");
        if(!empty($removeSales)){
            $removeSales->execute();
        }
        $this->em->getRepository(\App\Inventory\Entity\Sales::class)->insertApiSales($this->getUser()->getGlobalOption(),$android);

        /* @var $sales Sales */

        $salses = $this->em->getRepository(\App\Inventory\Entity\Sales::class)->findBy(['androidProcess' => $android]);
        foreach ($salses as $sales){
            if($sales->getProcess() == "Device"){
                $sales->setProcess('Done');
                $sales->setUpdated($sales->getCreated());
                $sales->setApprovedBy($this->getUser());
                $em->flush();
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertAccountSales($sales);
                $msg = "valid";
            }
        }
        if($msg == "valid"){
            $android->setStatus(true);
            $em->persist($android);
            $em->flush();
            $this->em->getRepository(\App\Inventory\Entity\Sales::class)->updateApiSalesPurchasePrice($android->getId());
        }
        if($msg == "valid"){
            return "success";
        }else{
            return "failed";
        }
    }

    public function androidSalesProcessAction($device)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->androidDeviceSalesProcess($device);
        exit;
    }


}
