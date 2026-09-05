<?php

namespace App\DomainUser\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessInvoice;
use App\Business\Form\AssociationInvoiceType;
use App\DomainUser\Event\AssociationSmsEvent;
use App\DomainUser\Form\MemberEditProfileType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\DomainUser\Entity\Customer;
use App\DomainUser\Form\CustomerType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customer controller.
 *
 */
class AssociationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly ToolManager $toolManager,
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


    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEMBER_ASSOCIATION')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $data['type'] = "member";
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        $batches = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->studentBatchChoiceList();
        $bloods = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->bloodsChoiceList();
        return $this->render('DomainUser/Association/index.html.twig', array(
            'entities' => $pagination,
            'batches' => $batches,
            'bloods' => $bloods,
            'searchForm' => $data,
        ));
    }


    /**
     * Creates a new Customer entity.
     *
     */
    public function createAction(Request $request)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = new Customer();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
        $entity = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $globalOption,'mobile'=>$mobile));
        if ($form->isValid() and empty($entity)) {
            $em = $this->em;
            $entity->setGlobalOption($globalOption);
	        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setMobile($mobile);
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('domain_customer'));
        }

        return $this->render('DomainUser/Association/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Customer entity.
     *
     * @param Customer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Customer $entity)
    {
        $em = $this->em->getRepository(\App\DomainUser\Entity\Customer::class);
        $form = $this->createForm(new MemberEditProfileType($em), $entity, array(
            'action' => $this->generateUrl('domain_association_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEMBER_ASSOCIATION')"))]
    public function newAction()
    {
        $entity = new Customer();
        $form   = $this->createCreateForm($entity);

        return $this->render('DomainUser/Association/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Customer entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $config,'id'=> $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ElectionMember entity.');
        }
        $html = $this->renderView('DomainUser/Association/profile.html.twig',
            array('entity' => $entity)
        );
        return New Response($html);
    }

     /**
     * Finds and displays a Customer entity.
     *
     */
    public function processAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption' => $config, 'id' => $id ));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Association Member entity.');
        }
        if ($entity->getProcess() == 'Pending'){
            $entity->setProcess('Checked');
            $entity->setCheckedBy($this->getUser());
        }elseif ($entity->getProcess() == 'Checked'){
            $entity->setProcess('Approved');
            $entity->setApprovedBy($this->getUser());
            /* @var $global GlobalOption */
            $global = $this->getUser()->getGlobalOption();
            if($global->getSmsSenderTotal() and $global->getSmsSenderTotal()->getRemaining() > 0 and $global->getNotificationConfig()->getSmsActive() == 1) {
                $dispatcher = $this->container->get('event_dispatcher');
                $msg = "Dear Member, Your registration successfully done!";
                $dispatcher->dispatch('appstore.customer.post.member_sms', new AssociationSmsEvent($entity,$msg));
            }

        }
        $em->persist($entity);
        $em->flush();
        return New Response("success");
    }

    /**
     * Finds and displays a Customer entity.
     *
     */
    public function groupSmsAction()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findBy(array('globalOption' => $config));
        /* @var $global GlobalOption */
        $global = $this->getUser()->getGlobalOption();
        if($global->getSmsSenderTotal() and $global->getSmsSenderTotal()->getRemaining() > 0 and $global->getNotificationConfig()->getSmsActive() == 1) {
            foreach ($entities as $entity){
                $msg = "Congratulation! your registration has done.your member ID is {$entity->getCustomerId()}. Please click www.bhaws.org for give your monthly payment info. Thanks BHAWS";
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->dispatch('appstore.customer.post.member_sms', new AssociationSmsEvent($entity, $msg));
            }
        }
        return New Response("success");
    }

    /**
     * Finds and displays a Customer entity.
     *
     */

    public function memberShowAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        return $this->render('DomainUser/Customer/memberShow.html.twig', array(
            'entity'      => $entity,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEMBER_ASSOCIATION')"))]
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('DomainUser/Association/editProfile.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Customer entity.
    *
    * @param Customer $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Customer $entity)
    {
        $em = $this->em->getRepository(\App\DomainUser\Entity\Customer::class);
        $form = $this->createForm(new MemberEditProfileType($em), $entity, array(
            'action' => $this->generateUrl('domain_association_profile_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing Customer entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
	        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setMobile($mobile);
            $em->flush();
            return $this->redirect($this->generateUrl('domain_association'));
        }

        return $this->render('DomainUser/Association/editProfile.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    public function batchSelectAction()
    {
        $entities = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->studentBatchChoiceList();
        $items = array();
        $items[] = array('value' => '','text'=> '-Select Batch-');
        foreach ($entities as $entity):
            $items[] = array('value' => $entity,'text'=> $entity);
        endforeach;
        $items[]=array('value' => '','text'=> 'Empty Batch');
        return new JsonResponse($items);
    }

    public function bloodSelectAction()
    {
        $items[] = array('value' => '','text'=> '-Blood-');
        $items[]=array('value' => 'A+','text'=> 'A+');
        $items[]=array('value' => 'A-','text'=> 'A-');
        $items[]=array('value' => 'B-','text'=> 'B-');
        $items[]=array('value' => 'B+','text'=> 'B+');
        $items[]=array('value' => 'O+','text'=> 'O+');
        $items[]=array('value' => 'O-','text'=> 'O-');
        $items[]=array('value' => 'AB+','text'=> 'AB+');
        $items[]=array('value' => 'AB-','text'=> 'AB-');
        $items[]=array('value' => '','text'=> 'Empty');
        return new JsonResponse($items);
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PurchaseItem entity.');
        }
        $setName = 'set'.$data['name'];
        $setValue = $data['value'];
        $entity->$setName($setValue);
        $em->persist($entity);
        $em->flush();
        exit;

    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEMBER_ASSOCIATION_ADMIN')"))]

    public function deleteAction($id)
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$globalOption,'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        $user = $this->em->getRepository(\App\User\Entity\User::class)->find($entity->getUser());
        if($user){
            $user->setEnabled(false);
            $user->setIsDelete(true);
            $user->setGlobalOption(NULL);
        }
        $entity->setGlobalOption(NULL);
        $entity->setStatus(false);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Data has been deleted successfully"
        );
        $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->deleteAssociationInvoice($entity);
        return new Response('Success');
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $go = $this->getUser()->getGlobalOption();
            $item = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->searchAutoComplete($go,$item);
        }
        return new JsonResponse($item);
    }

    public function searchCustomerNameAction($customer)
    {
        return new JsonResponse(array(
            'id'=> $customer,
            'text' => $customer
        ));
    }

    public function autoMobileSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $go = $this->getUser()->getGlobalOption();
            $item = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->searchAutoCompleteName($go,$item);
        }
        return new JsonResponse($item);
    }

    public function searchCustomerMobileAction($customer)
    {
        return new JsonResponse(array(
            'id'=> $customer,
            'text' => $customer
        ));
    }

    public function autoCodeSearchAction(Request $request)
    {

        $q = $_REQUEST['term'];
        $option = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->searchAutoCompleteCode($option,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('id' => $entity['customer'],'value' => $entity['text']);
        endforeach;
        return new JsonResponse($items);

    }

    public function searchCodeAction($customer)
    {
        return new JsonResponse(array(
            'id'=> $customer,
            'text' => $customer
        ));
    }


    public function autoLocationSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $item = $this->em->getRepository(\App\Location\Entity\Location::class)->searchAutoComplete($item);
        }
        return new JsonResponse($item);

    }

    public function searchLocationNameAction($location)
    {
        return new JsonResponse(array(
            'id'=> $location,
            'text' => $location
        ));
    }

    public function searchAutoCompleteNameAction()
    {
        $q = $_REQUEST['q'];
        $option = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->searchAutoCompleteName($option,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('id' => $entity['id'],'value' => $entity['id']);
        endforeach;
        return new JsonResponse($entities);

    }

    public function searchAutoCompleteMobileAction()
    {
        $q = $_REQUEST['term'];
        $option = $this->getUser()->getGlobalOption();
        $entities = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->searchAutoComplete($option,$q);
        $items = array();
        foreach ($entities as $entity):
            $items[]=array('id' => $entity['customer'],'value' => $entity['id']);
        endforeach;
        return new JsonResponse($items);

    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEMBER_ASSOCIATION')"))]

    public function invoiceNewAction($customer)
    {

        $em = $this->em;
        $option =  $this->getUser()->getGlobalOption();
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array("globalOption" => $option,"customerId" => $customer));

        $lastInvoice = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->getLastInvoiceParticular($customer);

        $entity = new BusinessInvoice();
        $editForm = $this->createInvoiceCreateForm($entity,$customer);
        $outstanding = 0;

        return $this->render("DomainUserBundle:Association/Invoice:new.html.twig", array(

            'globalOption'      => $this->getUser()->getGlobalOption(),
            'lastInvoice'       => $lastInvoice,
            'entity'            => $entity,
            'customer'          => $customer,
            'outstanding'       => $outstanding,
            'form'              => $editForm->createView(),

        ));

    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param BusinessInvoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createInvoiceCreateForm(BusinessInvoice $entity, Customer $customer)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AssociationInvoiceType::class, $entity, array('action' => $this->generateUrl('domain_association_invoice_create', array('customer' => $customer->getCustomerId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',

            ), 'globalOption' => $globalOption));
        return $form;
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEMBER_ASSOCIATION')"))]

    public function invoiceCreateAction(Request $request, $customer)
    {
        $data = $request->request->all();
        $user = $this->getUser();
        $config = $user->getGlobalOption()->getBusinessConfig();

        $option =  $this->getUser()->getGlobalOption();
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array("globalOption" => $option,"customerId" => $customer));
        $lastInvoice = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->getLastInvoiceParticular($customer);

        $entity = new BusinessInvoice();
        $form = $this->createInvoiceCreateForm($entity,$customer);
        $form->handleRequest($request);
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if (($form->isValid() && $method == 'cash') ||
            ($form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $em = $this->em;
            $entity->setBusinessConfig($config);
            $entity->setCustomer($customer);
            $entity->setMobile($customer->getMobile());
            $entity->setReceived($data['paymentTotal']);
            $entity->setDue(0);
            $entity->setEndDate($lastInvoice->getEndDate());
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Data has been inserted successfully"
            );
            $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertStudentMonthlyParticular($entity,$lastInvoice, $data);
            $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($entity);
            if($entity->getProcess() == "Done"){
                $accountSales = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessAccountInvoice($entity);
                $em->getRepository(\App\Accounting\Entity\Transaction::class)->serviceTransaction($accountSales);
                /* @var $global GlobalOption */
                $global = $this->getUser()->getGlobalOption();
                if($global->getSmsSenderTotal() and $global->getSmsSenderTotal()->getRemaining() > 0 and $global->getNotificationConfig()->getSmsActive() == 1) {
                    $dispatcher = $this->container->get('event_dispatcher');
                    $dispatcher->dispatch('setting_tool.post.association_invoice', new \App\Tool\Event\AssociationInvoiceSmsEvent($entity));
                }
            }
            return $this->redirect($this->generateUrl('domain_association_invoice'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'warning', "Payment information does not valid"
        );
        return $this->render("DomainUserBundle:Association/Invoice:new.html.twig", array(
            'globalOption' => $user->getGlobalOption(),
            'customer' => $customer,
            'lastInvoice'       => $lastInvoice,
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_CRM_ASSOCIATION')"))]
    public function invoiceAction()
    {

        $em = $this->em;
        $data = $_REQUEST;
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository( \App\Business\Entity\BusinessInvoice::class )->invoiceLists( $config->getId(),$data);
        $pagination = $this->paginate($entities);
        return $this->render("DomainUserBundle:Association/Invoice:index.html.twig", array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEMBER_ASSOCIATION')"))]

    public function invoiceShowAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->findOneBy(array('businessConfig' => $config,'id' => $id));
        return $this->render("DomainUserBundle:Association/Invoice:show.html.twig", array(
            'entity' => $entity,
        ));

    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN') or is_granted('ROLE_MEMBER_ASSOCIATION')"))]
     public function invoiceEditAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->findOneBy(array('businessConfig' => $config,'id' => $id));
        $form = $this->editInvoiceCreateForm($entity);
        return $this->render("DomainUserBundle:Association/Invoice:edit.html.twig", array(
            'entity' => $entity,
            'globalOption' => $entity->getCustomer()->getGlobalOption(),
            'form' => $form->createView(),
        ));

    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @param BusinessInvoice $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function editInvoiceCreateForm(BusinessInvoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(AssociationInvoiceType::class, $entity, array('action' => $this->generateUrl('domain_association_invoice_update', array('id'=>$entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',

            ), 'globalOption' => $globalOption));
        return $form;
    }

    public function invoiceUpdateAction(Request $request,$id)
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->findOneBy(array('businessConfig' => $config,'id' => $id));
        $form = $this->editInvoiceCreateForm($entity);
        $form->handleRequest($request);
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if (($form->isValid() && $method == 'cash') ||
            ($form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $em = $this->em;
            $entity->setBusinessConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Data has been inserted successfully"
            );
            if($entity->getProcess() == "Done"){
            $accountSales = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertBusinessAccountInvoice($entity);
            $em->getRepository(\App\Accounting\Entity\Transaction::class)->serviceTransaction($accountSales);
            }
            $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($entity);
            return $this->redirect($this->generateUrl('domain_association_invoice'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'warning', "Payment information does not valid"
        );
        return $this->render("DomainUserBundle:Association/Invoice:edit.html.twig", array(
            'globalOption' => $entity->getCustomer()->getGlobalOption(),
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    public function invoicePrintAction($id)
    {
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->findOneBy(array('businessConfig' => $config,'id' => $id));

        $em = $this->em;

        /* @var $config BusinessConfig */

        $print = (isset($_REQUEST['print']) and !empty($_REQUEST['print'])) ?  $_REQUEST['print'] : 'print';

        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        if ($config->getId() == $entity->getBusinessConfig()->getId()) {

            if($config->isCustomInvoicePrint() == 1){
                $template = 'invoice-'.$config->getGlobalOption()->getId();
            }else{
                $template = !empty($config->getInvoiceType()) ? $config->getInvoiceType():'print';
            }
            $amountInWords = '';
            if($entity->getProcess() == "Quotation"){
                $amountInWords = $this->toolManager->intToWords($entity->getTotal());
            }
            $result = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->customerSingleOutstanding($this->getUser()->getGlobalOption(),$entity->getCustomer());
            $balance = empty($result) ? 0 : $result;
            return  $this->render("Business/Print/{$template}.html.twig",
                array(
                    'config' => $config,
                    'entity' => $entity,
                    'balance' => $balance,
                    'amountInWords' => $amountInWords,
                    'print' => $print,
                )
            );
        }
    }



}
