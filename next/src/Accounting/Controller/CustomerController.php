<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Accounting\Form\CustomerType;
use App\Accounting\Form\UserType;
use App\User\Entity\Profile;
use App\User\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\DomainUser\Entity\Customer;


/**
 * Customer controller.
 *
 */
class CustomerController extends AbstractController
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


    #[IsGranted(new Expression("is_granted('ROLE_ACCOUNTING') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $data['userGroup'] = 'account';
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption,$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/Customer/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    public function restoreCustomerAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\User\Entity\User::class)->getEmployees($globalOption);
        /* @var $entity User */
        foreach ($entities as $entity){
            $mobile = $entity->getProfile()->getMobile();
            $exist = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(['globalOption'=>$globalOption,'mobile'=>$mobile]);
            if(empty($exist)){
                $customer = new Customer();
                $customer->setGlobalOption($globalOption);
                $customer->setCustomerType('account');
                $customer->setName($entity->getProfile()->getName());
                $customer->setMobile($entity->getProfile()->getMobile());
                $customer->setEmail($entity->getEmail());
                $customer->setLocation($entity->getProfile()->getLocation());
                $customer->setAddress($entity->getProfile()->getAddress());
                $em->persist($customer);
                $em->flush();
            }

        }
        return $this->redirect($this->generateUrl('account_customer'));
    }

    /**
     * Creates a new Customer entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Profile();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $data = $request->request->all();
        if ($form->isValid()) {
            $em = $this->em;
            $globalOption = $this->getUser()->getGlobalOption();
            $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setMobile($mobile);
	        $user = $this->em->getRepository(\App\User\Entity\User::class)->insertAccountUser($globalOption,$mobile,$data);
	        $entity->setUser($user);
            $em->persist($entity);
            $em->flush();
            if($entity->getUserGroup() != 'other'){
                $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->insertUserAccount($entity);
            }
            return $this->redirect($this->generateUrl('account_customer'));
        }

        return $this->render('Accounting/Customer/new.html.twig', array(
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
    private function createCreateForm(Profile $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(UserType::class, $entity, array('action' => $this->generateUrl('account_customer_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'user' => $location));
        return $form;
    }
    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function newAction()
    {
        $entity = new Profile();
        $form   = $this->createCreateForm($entity);
        return $this->render('Accounting/Customer/new.html.twig', array(
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

        $entity = $em->getRepository(\App\User\Entity\Profile::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }

        return $this->render('Accounting/Customer/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function editAction(User $user)
    {
        $em = $this->em;
        $entity = $user->getProfile();
     //   $entity = $em->getRepository(\App\User\Entity\Profile::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Accounting/Customer/new.html.twig', array(
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
    private function createEditForm(Profile $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(UserType::class, $entity, array('action' => $this->generateUrl('account_customer_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'user' => $location));
        return $form;
    }
    /**
     * Edits an existing Customer entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\User\Entity\Profile::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
	        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setMobile($mobile);
            $em->flush();
            return $this->redirect($this->generateUrl('account_customer'));
        }
        return $this->render('Accounting/Customer/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction($id)
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\User\Entity\Profile::class)->findOneBy(array('globalOption'=>$globalOption,'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
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
        }
        return $this->redirect($this->generateUrl('account_customer'));
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

}
