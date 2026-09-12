<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Accounting\Form\UserType;
use App\DomainUser\Entity\Customer;
use App\User\Entity\Profile;
use App\User\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;


/**
 * Customer controller.
 *
 */
class UserController extends AbstractController
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
        return $this->render('Accounting/User/index.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
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
        return $this->redirect($this->generateUrl('account_user'));
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
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $globalOption = $this->getUser()->getGlobalOption();
            $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setMobile($mobile);
	        $user = $this->em->getRepository(\App\User\Entity\User::class)->insertAccountUser($globalOption,$entity->getUserGroup());
	        $entity->setUser($user);
            $em->persist($entity);
            $em->flush();
            if($entity->getUserGroup() != 'other'){
                $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->insertUserAccount($entity);
            }
            return $this->redirect($this->generateUrl('account_user'));
        }

        return $this->render('Accounting/User/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Customer entity.
     *
     * @param Customer $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Profile $entity)
    {

        $form = $this->createForm(UserType::class, $entity, [
            'action' => $this->generateUrl('account_user_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function newAction()
    {
        $entity = new Profile();
        $form   = $this->createCreateForm($entity);
        return $this->render('Accounting/User/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Customer entity.
     *
     */
    public function showAction($id)
    {

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
        return $this->render('Accounting/User/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Customer entity.
     *
     * @param Customer $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Profile $entity)
    {

        $form = $this->createForm(UserType::class, $entity, [
            'action' => $this->generateUrl('account_user_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Customer entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        /* @var $entity Profile */

        $entity = $em->getRepository(\App\User\Entity\Profile::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
	        $mobile = $this->toolManager->specialExpClean($entity->getMobile());
	        $entity->setMobile($mobile);
	        if($entity->getUserGroup() =='marketing'){
                $entity->getUser()->setUserGroup('marketing');
            }
	        $em->persist($entity);
            $em->flush();
            if($entity->getUserGroup() != 'other'){
                $this->em->getRepository(\App\Accounting\Entity\AccountHead::class)->insertUserAccount($entity);
            }
            return $this->redirect($this->generateUrl('account_user'));
        }

        return $this->render('Accounting/User/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]
    public function deleteAction($id)
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\User\Entity\Profile::class)->findOneBy(['globalOption'=>$globalOption,'id' => $id]);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        try {
            $entity->setIsDelete(true);
            $entity->setEnabled(false);
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }
        return $this->redirect($this->generateUrl('account_user'));
    }


    public function changeUserModeAction(User $user)
    {
        $em = $this->em;
        if($user->getUserGroup() == "account" ){
            $user->setUserGroup('user');
            $user->setEnabled(true);
        }else{
            $user->setUserGroup('account');
        }
        $em->persist($user);
        $em->flush();
        return $this->redirect($this->generateUrl('account_user'));
    }





}
