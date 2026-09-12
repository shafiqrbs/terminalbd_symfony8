<?php

namespace App\Accounting\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountPurchase;
use App\Accounting\Form\AccountHmsPurchaseType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Accounting\Entity\AccountSales;
use App\Accounting\Form\AccountSalesType;
use Symfony\Component\HttpFoundation\Response;

/**
 * AccountSalesHospital controller.
 *
 */
class AccountRestaurantController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists all AccountSales entities.
     *
     */
    public function salesAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Accounting\Entity\AccountSales::class)->findWithSearch($user,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->salesOverview($user,$data);
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccountHead($parent =array(20,29));
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        return $this->render('Accounting/AccountRestaurant/sales.html.twig', array(
            'entities' => $pagination,
            'accountHead' => $accountHead,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
            'overview' => $overview,
        ));
    }

    /**
     * Lists all AccountSales entities.
     *
     */
    public function purchaseAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountPurchaseOverview($globalOption,$data);
        $accountHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getChildrenAccountHead($parent =array(5));
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
        return $this->render('Accounting/AccountHospital/purchase.html.twig', array(
            'entities' => $pagination,
            'accountHead' => $accountHead,
            'transactionMethods' => $transactionMethods,
            'searchForm' => $data,
            'overview' => $overview,
        ));
    }

    public function purchaseNewAction()
    {
        $em = $this->em;
        $entity = new AccountPurchase();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        return $this->render('Accounting/AccountHospital/purchaseNew.html.twig', array(
            'entity'    => $entity,
            'banks'     => $banks,
            'form'      => $form->createView(),
        ));
    }

    public function purchaseCreateAction(Request $request)
    {
        $entity = new AccountPurchase();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $entity->setProcessHead('Account Purchase');
            if($entity->getPayment() < 0){
                $entity->setPurchaseAmount(abs($entity->getPayment()));
                $entity->setPayment(0);
                $entity->setTransactionMethod(null);
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_purchase_hospital'));
        }

        return $this->render('Accounting/AccountHospital/purchaseNew.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Creates a form to create a AccountPurchase entity.
     *
     * @param AccountPurchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(AccountPurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AccountHmsPurchaseType::class, $entity, array('action' => $this->generateUrl('account_purchase_hospital_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }


}
