<?php

namespace App\DomainUser\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\DomainUser\Entity\Customer;
use App\DomainUser\Form\CustomerEditType;
use App\DomainUser\Form\CustomerType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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


    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        return $this->render('DomainUser/Customer/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

     #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]

    public function memberAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $data['type'] = "member";
        $entities = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
        return $this->render('DomainUser/Member/member.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
        ));
    }

    /**
     * Creates a new Customer entity.
     *
     */
    public function createAction(Request $request)
    {
        $userId = $this->getUser()->getId();
        $entity = new Customer();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $globalOption = $this->getUser()->getGlobalOption();
        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
        $exist = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$globalOption, 'mobile'=>$mobile));
        if ($form->isValid() and empty($exist)) {
            $em = $this->em;
            $entity->setGlobalOption($globalOption);
	        $entity->setMobile($mobile);
            $em->persist($entity);
            $em->flush();
            if ($entity->getOpeningBalance() > 0){
                $em->getRepository(\App\Accounting\Entity\AccountSales::class)->apiInsertOpeningBalance($entity,$userId,$entity->getOpeningBalance());
            }
            return $this->redirect($this->generateUrl('domain_customer'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"May be you are using existing mobile no"
        );
        $option = $this->getUser()->getGlobalOption();
        $customerGroups = $this->em->getRepository(Customer::class)->findcustomerGroups($option->getId());
        return $this->render('DomainUser/Customer/new.html.twig', array(
            'customerGroups' => $customerGroups,
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
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into CustomerType::configureOptions() and pass them as form options: $globalOption,$location), $entity, array(
        $form = $this->createForm(new CustomerType($globalOption,$location), $entity, array(
            'action' => $this->generateUrl('domain_customer_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function newAction()
    {
        $entity = new Customer();
        $form   = $this->createCreateForm($entity);
        $option = $this->getUser()->getGlobalOption();
        $customerGroups = $this->em->getRepository(Customer::class)->findcustomerGroups($option->getId());
        return $this->render('DomainUser/Customer/new.html.twig', array(
            'customerGroups' => $customerGroups,
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

        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }

        return $this->render('DomainUser/Customer/show.html.twig', array(
            'entity'      => $entity,
        ));
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

    /**
     * Finds and displays a Customer entity.
     *
     */
    public function rehabAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }

        return $this->render('DomainUser/Customer/rehab.html.twig', array(
            'entity'      => $entity,
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        $editForm = $this->createEditForm($entity);
        $customerGroups = $this->em->getRepository(Customer::class)->findcustomerGroups($entity->getGlobalOption()->getId());
        return $this->render('DomainUser/Customer/edit.html.twig', array(
            'entity'      => $entity,
            'customerGroups'      => $customerGroups,
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
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $globalOption = $this->getUser()->getGlobalOption();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into CustomerEditType::configureOptions() and pass them as form options: $globalOption,$location), $entity, array(
        $form = $this->createForm(new CustomerEditType($globalOption,$location), $entity, array(
            'action' => $this->generateUrl('domain_customer_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
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
        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
        $count = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->customerCount($entity->getGlobalOption()->getId(),$entity->getId(),$mobile);
        if ($editForm->isValid() and $count == 0) {
	        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setMobile($mobile);
            $em->flush();
            return $this->redirect($this->generateUrl('domain_customer'));
        }
        if($count > 0){
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"May be you are using existing mobile no"
            );
        }
        return $this->render('DomainUser/Customer/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction($id)
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$globalOption,'id' => $id));
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
        return $this->redirect($this->generateUrl('customer'));
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        $go = $this->getUser()->getGlobalOption();
        if ($item and $go) {
            $item = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->searchAutoComplete($go,$item);
        }
        return new JsonResponse($item);
    }

     public function patientSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        $go = $this->getUser()->getGlobalOption();
        if ($item and $go) {
            $item = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->searchPatientAutoComplete($go,$item);
        }
        return new JsonResponse($item);
    }

    public function patientInfoAction(Customer $customer)
    {
        $data = array(
            'id'=> $customer->getId(),
            'patientId'=> $customer->getCustomerId(),
            'name'=> $customer->getName(),
            'mobile'=> $customer->getMobile(),
            'age'=> $customer->getAge(),
            'ageType'=> $customer->getAgeType(),
            'gender'=> $customer->getGender(),
            'height'=> $customer->getHeight(),
            'weight'=> $customer->getWeight(),
            'bloodPressure'=> $customer->getBloodPressure(),
            'address'=> $customer->getAddress()
        );
        return new Response(json_encode($data));
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
        $q = $_REQUEST['term'];
        $option = $this->getUser()->getGlobalOption();
        $items = array();
        if($q and $option){
            $entities = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->searchAutoCompleteName($option,$q);
            foreach ($entities as $entity):
                $items[]=array('id' => $entity['id'],'value' => $entity['id']);
            endforeach;
        }
        return new JsonResponse($items);

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
