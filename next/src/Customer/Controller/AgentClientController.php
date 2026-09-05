<?php

namespace App\Customer\Controller;


use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\User\Entity\Profile;
use App\User\Entity\User;
use App\User\Form\AgentProfileType;
use App\User\Form\CustomerEditProfileType;
use App\User\Form\SignupType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentClientController extends AbstractController
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

        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Lists all GlobalOption entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->findBy(array('agent' => $user));
        $entities = $this->paginate($entities);
        return $this->render('Customer/Agent/index.html.twig', array(
            'entities' => $entities,
            'user' => $user,
            'globalOption' => '',
        ));
    }
    /**
     * Creates a new GlobalOption entity.
     *
     */
    public function createAction(Request $request)
    {

        $entity = new User();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $intlMobile = $entity->getProfile()->getMobile();
        $mobile = $this->toolManager->specialExpClean($intlMobile);
        $entity->getProfile()->setMobile($mobile);
        $data = $request->request->all();
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $globalOption = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->createGlobalOption($mobile,$data,$user);
            $entity->setPlainPassword("1234");
            $entity->setGlobalOption($globalOption);
            $entity->setEnabled(true);
            $entity->setDomainOwner(true);
            $entity->setUsername($mobile);
            $entity->setEmail($mobile.'@gmail.com');
            $entity->setRoles(array('ROLE_DOMAIN'));
            $em = $this->em;
            $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->initialSetup($entity);
            $this->toolManager->createDirectory($entity->getGlobalOption()->getId());

            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch(new \App\Tool\Event\UserSignup($entity), 'setting_tool.post.user_signup_msg');
            return $this->redirect($this->generateUrl('agentclient'));
        }

        return $this->render('Customer/Agent/signup.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));

    }

    private function createCreateForm(User $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $em = $this->em->getRepository(\App\Tool\Entity\Syndicate::class);
        $form = $this->createForm(SignupType::class, $entity, array('action' => $this->generateUrl('agentclient_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'id' => 'signup',
                'class' => 'registration signupForm',
                'novalidate' => 'novalidate',
            ), 'em' => $em, 'location' => $location));

        return $form;
    }


    /**
     * Displays a form to create a new GlobalOption entity.
     *
     */
    public function newAction()
    {
        $user = $this->getUser();
        $entity = new User();
        $form   = $this->createCreateForm($entity);
        return $this->render('Customer/Agent/signup.html.twig', array(
            'entity' => $entity,
            'user' => $user,
            'globalOption' => '',
            'form'   => $form->createView(),
        ));
    }


    /**
     * Creates a form to edit a DomainUser entity.
     *
     * @param User $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditProfileForm(Profile $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(AgentProfileType::class, $entity, array('action' => $this->generateUrl('customer_self_update_profile'),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'location' => $location));
        return $form;
    }

    /**
     * Displays a form to edit an existing DomainUser entity.
     *
     */
    public function editAction()
    {
        $user = $this->getUser();
        $editForm = $this->createEditProfileForm($user->getProfile());
        return $this->render('DomainUser/Agent/profile.html.twig', array(
            'globalOption' => '',
            'entity'      => $user,
            'form'   => $editForm->createView(),
        ));
    }

    public function profileUpdateAction(Request $request)
    {
        $em = $this->em;

        $user = $this->getUser();

        /* @var Profile $entity */

        $entity = $this->getUser()->getProfile();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DomainUser entity.');
        }

        $editForm = $this->createEditProfileForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $intlMobile = $entity->getMobile();
            $mobile = $this->toolManager->specialExpClean($intlMobile);
            $entity->setMobile($mobile);
            if($entity->upload() &&  !empty($entity->getFile()) and !empty($entity->getPath()) ){
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('customer_self_edit_profile'));
        }

        return $this->render('DomainUser/Agent/profile.html.twig', array(
            'globalOption' => '',
            'entity'      => $user,
            'form'   => $editForm->createView(),

        ));
    }



}
