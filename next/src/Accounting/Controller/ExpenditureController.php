<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Entity\ExpenseAndroidProcess;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Accounting\Entity\Expenditure;
use App\Accounting\Form\ExpenditureType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expenditure controller.
 *
 */
class ExpenditureController extends AbstractController
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
     * Lists all Expenditure entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->findWithSearch( $this->getUser(),$data);
        $pagination = $this->paginate($entities);
        $overview = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->expenditureOverview($user,$data);
     //   $flatExpenseCategoryTree = $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->getCategoryOptions( $user->getGlobalOption());
        $transactionMethods = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findBy(array('status'=>1),array('name'=>'asc'));
	    $categories = $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->findBy(array('globalOption'=> $user->getGlobalOption(), 'status'=>1),array('name'=>'asc'));
	    $heads = $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->getExpenseAccountHead();
        $employees = $em->getRepository(\App\User\Entity\User::class)->getEmployees($user->getGlobalOption());
        return $this->render('Accounting/Expenditure/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
            'flatExpenseCategoryTree' => '',
            'transactionMethods' => $transactionMethods,
            'overview' => $overview,
            'heads' => $heads,
            'global' => $user->getGlobalOption()->getId(),
            'employees'=> $employees,
            'categories' => $categories,
        ));
    }
    /**
     * Creates a new Expenditure entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Expenditure();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $em = $this->em;
        $data = $request->request->all();
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if (($form->isValid() && $method == 'cash') ||
            ($form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            $lastBalance = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->lastInsertExpenditure($entity);
            $em = $this->em;
            $entity->setGlobalOption( $this->getUser()->getGlobalOption());
            $entity->setBalance($lastBalance + $entity->getAmount());
            $entity->setAccountHead($entity->getExpenseCategory()->getAccountHead());
            if($this->getUser()->getProfile()->getBranches()){
                $entity->setBranches($this->getUser()->getProfile()->getBranches());
            }
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if(isset($data['created']) and !empty($data['created'])){
                $created = $data['created'];
                $date = new \DateTime($created);
                $entity->setCreated($date);
                $entity->setUpdated($date);
            }elseif($accountConfig == 1){
                $datetime = new \DateTime("yesterday 23:30:30");
                $entity->setCreated($datetime);
                $entity->setUpdated($datetime);
            }else{
                $datetime = new \DateTime("now");
                $entity->setUpdated($datetime);
            }
            $entity->setUpdated($entity->getCreated());
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('account_expenditure'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are missing to select bank or mobile account"
        );
        return $this->render('Accounting/Expenditure/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Expenditure entity.
     *
     * @param Expenditure $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Expenditure $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $expenseCategory = $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class);
        $form = $this->createForm(ExpenditureType::class, $entity, array('action' => $this->generateUrl('account_expenditure_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal purchase',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption, 'expenseCategoryRepository' => $expenseCategory));
      return $form;
    }

    /**
     * Displays a form to create a new Expenditure entity.
     *
     */
    public function newAction()
    {
        $em = $this->em;
        $entity = new Expenditure();
        $form   = $this->createCreateForm($entity);
        $banks = $em->getRepository(\App\Tool\Entity\Bank::class)->findAll();
        return $this->render('Accounting/Expenditure/new.html.twig', array(
            'entity' => $entity,
            'banks' => $banks,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Displays a form to edit an existing Expenditure entity.
     *
     */
    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $Expenditure = $em->getRepository(\App\Accounting\Entity\Expenditure::class)->find($data['pk']);
        if (!$Expenditure) {
            throw $this->createNotFoundException('Unable to find Expenditure entity.');
        }
        $Expenditure->setAmount($data['value']);
        $em->flush();
        exit;
    }

    public function approveAction(Expenditure $expenditure)
    {
        if (!empty($expenditure) and $expenditure->getProcess() != "approved" ) {
            $em = $this->em;
            $expenditure->setProcess('approved');
            $expenditure->setApprovedBy($this->getUser());
            $accountConfig = $this->getUser()->getGlobalOption()->getAccountingConfig()->isAccountClose();
            if($accountConfig == 1){
                $datetime = new \DateTime("yesterday 23:30:30");
                $expenditure->setCreated($datetime);
                $expenditure->setUpdated($datetime);
            }
            $em->flush();
            $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertExpenditureCash($expenditure);
           // $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->insertExpenditureTransaction($expenditure);
            return new Response('success');
        } else {
            return new Response('failed');
        }

    }

    /**
     * Deletes a Expenditure entity.
     *
     */
    public function deleteAction(Expenditure $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Expenditure entity.');
        }
        if($entity->upload() && !empty($entity->getFile())){
            $entity->removeUpload();
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');

    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNT_REVERSE') or is_granted('ROLE_DOMAIN')"))]

    public function expenditureReverseAction(Expenditure $entity){

        $em = $this->em;
        $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->accountReverse($entity);
        $entity->setProcess(null);
        $entity->setApprovedBy(null);
        $entity->setAmount(0);
        $entity->setBalance(0);
        $em->flush();
        return $this->redirect($this->generateUrl('account_expenditure'));

    }


    public function androidExpenseAction()
    {
        $conf = $this->getUser()->getGlobalOption()->getId();
        $entities = $this->em->getRepository(\App\Accounting\Entity\ExpenseAndroidProcess::class)->getAndroidSalesList($conf);
        $pagination = $this->paginate($entities);
        $sales = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->findAndroidDeviceSales($pagination);
        return $this->render('@Accounting/Expenditure/android.html.twig', array(
            'entities' => $pagination,
            'sales' => $sales,
        ));
    }

    public function insertAndroidExpenseAction(ExpenseAndroidProcess $android)
    {
        $msg = "invalid";
        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption();

        $removeSales = $em->createQuery("DELETE AccountingBundle:Expenditure e WHERE e.androidProcess= {$android->getId()}");
        if(!empty($removeSales)){
            $removeSales->execute();
        }
        $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->insertApiExpense($config,$android);

        /* @var $sales Expenditure */

        foreach ($android->getExpendituries() as $sales){

            if($sales->getProcess() == "Device"){
                $sales->setProcess('approved');
                $sales->setUpdated($sales->getCreated());
                $sales->setApprovedBy($this->getUser());
                $em->flush();
                $this->em->getRepository(\App\Accounting\Entity\AccountCash::class)->insertExpenditureCash($sales);
                $msg = "valid";
            }
        }

        if($msg == "valid"){
            $android->setStatus(true);
            $em->persist($android);
            $em->flush();
        }
        if($msg == "valid"){
            return new Response('success');
        }else{
            return new Response('failed');
        }
    }

}
