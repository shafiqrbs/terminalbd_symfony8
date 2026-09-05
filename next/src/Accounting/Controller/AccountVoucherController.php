<?php

namespace App\Accounting\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\AccountPurchase;
use App\Accounting\Entity\BusinessParticular;
use App\Accounting\Entity\BusinessPurchase;
use App\Accounting\Entity\BusinessPurchaseItem;
use App\Accounting\Entity\ExpenditureItem;
use App\Accounting\Entity\VoucherItem;
use App\Accounting\Form\PurchaseType;
use App\Accounting\Form\VoucherType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor controller.
 *
 */
class AccountVoucherController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly Environment $twig,
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
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }


    /**
     * Lists all Vendor entities.
     *
     */

    public function indexAction()
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption();
        $data = $_REQUEST;
        $data['processHead'] = 'Expense';
        $entities = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findWithSearch($config,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/AccountVoucher/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    public function newAction()
    {
        $em = $this->em;
        $entity = new AccountPurchase();
        $config = $this->getUser()->getGlobalOption();
        $entity->setGlobalOption($config);
        $entity->setCreatedBy($this->getUser());
        $entity->setProcessHead('Voucher');
        $entity->setUpdated($entity->getCreated());
        $transactionMethod = $em->getRepository(\App\Tool\Entity\TransactionMethod::class)->find(1);
        $entity->setTransactionMethod($transactionMethod);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('account_voucher_edit', array('id' => $entity->getId())));
    }


    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getId();
        $entity = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findOneBy(array('globalOption' => $config , 'id' => $id));
        $products = '';
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render("Accounting/AccountVoucher/new.html.twig",array(
            'entity' => $entity,
            'products' => $products,
            'id' => 'purchase',
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param AccountPurchase $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(AccountPurchase $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $emHead = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class);
        $form = $this->createForm(VoucherType::class, $entity, array('action' => $this->generateUrl('account_voucher_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'purchase',
                'novalidate' => 'novalidate',
            ), 'option' => $globalOption, 'accountHead' => $emHead));
        return $form;
    }

    public function returnResultData(AccountPurchase $invoice,$msg=''){

        $invoiceParticulars = $this->em->getRepository('AccountingBundle:VoucherItem')->getPurchaseItems($invoice);
        $subTotal = $invoice->getTotalAmount() > 0 ? $invoice->getTotalAmount() : 0;
        $payment = $invoice->getPayment() > 0 ? $invoice->getPayment() : 0;
        $data = array(
            'subTotal' => $subTotal,
            'payment' => $payment,
            'invoiceParticulars' => $invoiceParticulars ,
            'msg' => $msg ,
            'success' => 'success'
        );

        return $data;

    }


    public function addParticularAction(Request $request, AccountPurchase $invoice)
    {
        $em = $this->em;
        $productGroup = $request->request->get('productGroup');
        $particular = $request->request->get('name');
        $quantity = $request->request->get('quantity');
        $price = $request->request->get('price');
        $invoiceItems = array('productGroup' => $productGroup , 'name' => $particular , 'quantity' => $quantity,'price' => $price);
        $this->em->getRepository('AccountingBundle:VoucherItem')->insertPurchaseItems($invoice, $invoiceItems);
        $invoice = $this->em->getRepository('AccountingBundle:VoucherItem')->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));
        exit;

    }


    public function invoiceParticularDeleteAction(AccountPurchase $invoice, VoucherItem $particular){

        $em = $this->em;
        if (!$particular) {
            throw $this->createNotFoundException('Unable to find SalesItem entity.');
        }
        $em->remove($particular);
        $em->flush();
        $invoice = $this->em->getRepository('AccountingBundle:VoucherItem')->updatePurchaseTotalPrice($invoice);
        $result = $this->returnResultData($invoice);
        return new Response(json_encode($result));
        exit;
    }

    #[IsGranted('ROLE_BUSINESS')]
    public function invoiceDiscountUpdateAction(Request $request)
    {

    }

    public function updateAction(Request $request, AccountPurchase $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountVendor entity.');
        }

        $form = $this->createEditForm($entity);
        $form->handleRequest($request);
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if (($form->isSubmitted() && $form->isValid() && $method == 'cash') ||
            ($form->isSubmitted() && $form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isSubmitted() && $form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $entity->setCompanyName($entity->getAccountVendor()->getCompanyName());
            $entity->setUpdated($entity->getCreated());
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_voucher_show',['id'=>$entity->getId()]));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        if($entity->getProcess() == 'Approved'){
            $this->approvedAction($entity->getId());
        }
        return $this->render("Accounting/AccountVoucher/new.html.twig",[
            'entity' => $entity,
            'id' => 'purchase',
            'form' => $form->createView(),
        ]);
    }


    /**
     * Finds and displays a Vendor entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $config = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findOneBy(array('globalOption' => $config , 'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        return $this->render('Accounting/AccountVoucher/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    public function approvedAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption();

        /* @var $purchase AccountPurchase */

        $purchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findOneBy(array('globalOption' => $config , 'id' => $id));
        if (!empty($purchase) and empty($purchase->getApprovedBy())) {
            $em = $this->em;
            $purchase->setProcess('approved');
            $purchase->setApprovedBy($this->getUser());
            if($purchase->getPayment() === 0 ){
                $purchase->setTransactionMethod(NULL);
            }
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new \DateTime("yesterday 30:30:30");
                $purchase->setCreated($datetime);
                $purchase->setUpdated($datetime);
            }
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->updateVendorBalance($purchase);
            $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertPurchaseExpenditureCash($purchase);
            return new Response('success');
        } else {
            return new Response('failed');
        }
        exit;
    }

    /**
     * Deletes a Vendor entity.
     *
     */
    public function deleteAction($id)
    {

        $config = $this->getUser()->getGlobalOption();
        $entity = $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->findOneBy(array('globalOption' => $config , 'id' => $id));

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Vendor entity.');
        }
        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('account_voucher'));
    }



    public function reverseAction($id)
    {

        /*
         * Item Remove Total quantity
         * Stock Details
         * Purchase Item
         * Purchase Vendor Item
         * Purchase
         * Account Purchase
         * Account Journal
         * Transaction
         * Delete Journal & Account Purchase
         */
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $purchase = $this->em->getRepository('AccountingBundle:BusinessPurchase')->findOneBy(array('businessConfig' => $config , 'id' => $id));

        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->em;
        if($purchase->getAsInvestment() == 1 ) {
            $this->em->getRepository(\App\Accounting\Entity\AccountJournal::class)->removeApprovedBusinessPurchaseJournal($purchase);
        }
        $this->em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->accountBusinessPurchaseReverse($purchase);
        $purchase->setIsReversed(true);
        $purchase->setProcess('created');
        $purchase->setApprovedBy(NULL);
        $em->flush();
        //$this->em->getRepository('AccountingBundle:BusinessParticular')->getPurchaseUpdateQnt($purchase);
        $template = $this->twig->render('Accounting/AccountVoucher/purchaseReverse.html.twig', array(
            'entity' => $purchase,
            'config' => $purchase->getBusinessConfig(),
        ));
        //$this->em->getRepository('AccountingBundle:BusinessReverse')->purchaseReverse($purchase, $template);
        return $this->redirect($this->generateUrl('account_voucher_edit',array('id' => $purchase->getId())));
    }


    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

    }

    public function autoSearchAction(Request $request)
    {

    }

    public function searchVendorNameAction($vendor)
    {

    }

}
