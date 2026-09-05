<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Inventory\Entity\Item;
use App\Inventory\Form\InventoryItemType;
use App\Inventory\Form\SalesGeneralType;
use App\Inventory\Form\SalesItemType;
use App\Inventory\Form\SalesManualType;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\Inventory\Entity\SalesItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Inventory\Entity\Sales;
use App\Inventory\Form\SalesType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sales controller.
 *
 */
class SalesManualController extends AbstractController
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

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function indexAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->salesLists($this->getUser(), $mode ='manual',$data);
        $pagination = $this->paginate($entities);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status' => 1), array('name' => 'ASC'));
        return $this->render('Inventory/SalesManual/index.html.twig', array(
            'entities' => $pagination,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
        ));
    }


    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function newAction(Request $request)
    {

        $em = $this->em;

        $entity = new Sales();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $globalOption = $this->getUser()->getGlobalOption();
        $customer = $em->getRepository(\App\DomainUser\Entity\Customer::class)->defaultCustomer($globalOption);
        $entity->setCustomer($customer);
        $entity->setMobile($customer->getMobile());
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $entity->setSalesMode('Manual');
        $entity->setPaymentStatus('Pending');
        $entity->setInventoryConfig($inventory);
        $entity->setSalesBy($this->getUser());
        if(!empty($this->getUser()->getProfile()->getBranches())){
            $entity->setBranches($this->getUser()->getProfile()->getBranches());
        }
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('inventory_salesmanual_edit', array('code' => $entity->getInvoice())));

    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function addItemAction(Request $request)
    {

        $em = $this->em;

        $entity = new Sales();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $globalOption = $this->getUser()->getGlobalOption();
        $customer = $em->getRepository(\App\DomainUser\Entity\Customer::class)->defaultCustomer($globalOption);
        $entity->setCustomer($customer);
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $entity->setSalesMode('Manual');
        $entity->setPaymentStatus('Pending');
        $entity->setInventoryConfig($inventory);
        $entity->setSalesBy($this->getUser());
        if(!empty($this->getUser()->getProfile()->getBranches())){
            $entity->setBranches($this->getUser()->getProfile()->getBranches());
        }
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('inventory_salesmanual_edit', array('code' => $entity->getInvoice())));

    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function editAction($code)
    {

        $em = $this->em;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entity = $em->getRepository(\App\Inventory\Entity\Sales::class)->findOneBy(array('inventoryConfig' => $inventory, 'invoice' => $code));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Sales entity.');
        }
        $createItemForm = $this->createItemForm(New SalesItem(),$entity);
        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/SalesManual/sales.html.twig', array(
            'entities' => '',
            'inventory' => $inventory,
            'entity' => $entity,
            'form' => $editForm->createView(),
            'itemForm' => $createItemForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Sales entity.wq
     *
     * @param Sales $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Sales $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(SalesType::class, $entity, array('action' => $this->generateUrl('inventory_sales_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'id' => 'posForm',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }


    private function createItemForm(SalesItem $item , Sales $entity)
    {
        $form = $this->createForm(SalesItemType::class, $item, array('action' => $this->generateUrl('inventory_salesmanual_insert_item', array('sales' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'option' => $entity->getInventoryConfig()));
        return $form;
    }


}
