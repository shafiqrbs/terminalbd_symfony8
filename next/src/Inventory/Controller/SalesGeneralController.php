<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Inventory\Entity\Sales;
/**
 * Sales controller.
 *
 */
class SalesGeneralController extends AbstractController
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
        $entities = $em->getRepository(\App\Inventory\Entity\Sales::class)->salesLists($this->getUser(), $mode='general',$data);
        $pagination = $this->paginate($entities);
        $transactionMethods = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(['status' => 1], ['name' => 'ASC']);
        return $this->render('Inventory/SalesGeneral/index.html.twig', [
            'entities' => $pagination,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
        ]);
    }


    public function customerAction()
    {
        $em = $this->em;

        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/SalesGeneral/customer.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }


    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function newAction()
    {
        $customer = $_REQUEST['customer'] ?? '';
        $em = $this->em;
        $entity = new Sales();
        $globalOption = $this->getUser()->getGlobalOption();

        $customerEntity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->find($customer);
        if (!empty($customer) && !empty($customerEntity)) {
            $entity->setCustomer($customerEntity);
        }

        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $entity->setSalesMode('general');
        $entity->setPaymentStatus('Pending');
        $entity->setInventoryConfig($globalOption->getInventoryConfig());
        $entity->setSalesBy($this->getUser());
        if(!empty($this->getUser()->getProfile()->getBranches())){
            $entity->setBranches($this->getUser()->getProfile()->getBranches());
        }
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('inventory_salesgeneral_edit', ['code' => $entity->getInvoice()]));

    }


}
