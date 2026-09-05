<?php

namespace App\Business\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\BusinessStore;
use App\Business\Entity\BusinessStoreLedger;
use App\Business\Form\BusinessStoreLedgerType;
use App\Business\Form\BusinessStoreType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;

/**
 * BusinessStore controller.
 *u
 */
class BusinessStoreLedgerController extends AbstractController
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
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }

    /**
     * Lists all BusinessStore entities.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function indexAction()
    {

        $data = $_REQUEST;
        $global = $this->getUser()->getGlobalOption();
        $config = $global->getBusinessConfig();
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entities = $em->getRepository(\App\Business\Entity\BusinessStoreLedger::class)->invoiceLists($option,$data);
        $pagination = $this->paginate($entities);
        $stores = $this->em->getRepository(\App\Business\Entity\BusinessStore::class)->findBy(array('businessConfig' => $config,'status'=>1),array('name'=>"ASC"));
        $customers = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findBy(array('globalOption' => $global),array('name'=>"ASC"));
        return $this->render('Business/BusinessStoreLedger/index.html.twig', array(
            'entities' => $pagination,
            'customers' => $customers,
            'stores' => $stores,
            'searchForm' => $data,
        ));
    }

    /**
     * Displays a form to create a new Vendor entity.
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $entity = new BusinessStoreLedger();
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $form   = $this->createCreateForm($entity);
        return $this->render('Business/BusinessStoreLedger/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }




    /**
     * Creates a new BusinessStore entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $entity = new BusinessStoreLedger();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity->setBusinessConfig($option);
            $transaction = $entity->getTransactionType();
            if($transaction == 'Receive' || $transaction == 'Adjustment') {
                $entity->setAmount("-{$entity->getAmount()}");
                $entity->setCredit(abs($entity->getAmount()));
            }else{
                $entity->setDebit($entity->getAmount());
            }
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('business_store_ledger', array('id' => $entity->getId())));
        }
        return $this->render('Business/BusinessStoreLedger/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a BusinessStore entity.
     *
     * @param BusinessStore $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(BusinessStoreLedger $entity)
    {
        $option = $this->getUser()->getGlobalOption();
        $form = $this->createForm(BusinessStoreLedgerType::class, $entity, array('action' => $this->generateUrl('business_store_ledger_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'option' => $option));
        return $form;
    }


    /**
     * Deletes a BusinessStore entity.
     *
     * ;
     */
    #[IsGranted(new Expression("is_granted('ROLE_BUSINESS_STOCK') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction(BusinessStoreLedger $entity)
    {
        $em = $this->em;
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('business_area'));
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $this->em->getRepository(\App\Business\Entity\BusinessStoreLedger::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }
        $status = $entity->isStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setApprovedBy($this->getUser());
            $entity->setStatus(true);
            $this->em->getRepository(\App\Business\Entity\BusinessStoreLedger::class)->approveStorePayment($entity,$this->getUser());
            if($entity->getTransactionType() == "Receive"){
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertStoreLedgerPayment($entity);
            }elseif($entity->getTransactionType() == "Opening" || $entity->getTransactionType() == "Due"){
                $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->insertStoreOpeningPayment($entity);
            }
        }
        $em->flush();
        return new Response('success');

    }

}
