<?php

namespace App\DomainUser\Controller;
use App\User\Form\DomainEditUserType;
use FOS\UserBundle\Model\UserManager;
use App\User\Entity\User;
use App\User\Form\DomainEditSignType;
use App\User\Form\DomainSignType;
use App\Tool\Entity\Designation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\DomainUser\Entity\DomainUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * DomainUser controller.
 *
 */
class DomainUserController extends Controller
{

    /**
     * Lists all DomainUser entities.
     *
     */
    public function indexAction()
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $option = $user->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\User\Entity\User::class)->getEmployeeEntities($option);
        return $this->render('DomainUser/DomainUser/index.html.twig', array(
            'entities' => $entities,
        ));
    }


    /**
     * Creates a new DomainUser entity.
     *
     */
    public function createAction(Request $request)
    {

        $user = $this->getUser();
        $globalOption = $user->getGlobalOption();
        $entity = new User();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
       $data = $request->request->all();
       $password = $data['user']['plainPassword']['first'];
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $entity->setGlobalOption($globalOption);
            $entity->getProfile()->upload();
            $entity->setDomainOwner(2);
            $entity->setUserGroup('user');
            $entity->setAppPassword($password);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('domain_user'));
        }

        return $this->render('DomainUser/DomainUser/new.html.twig', array(
            'globalOption' => $globalOption,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a DomainUser entity.
     *
     * @param DomainUser $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(User $entity)
    {
        $user = $this->getDoctrine()->getRepository(\App\User\Entity\User::class);
        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->getDoctrine()->getRepository(\App\Location\Entity\Location::class);
        $designation = $this->getDoctrine()->getRepository(\App\Tool\Entity\Designation::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into DomainSignType::configureOptions() and pass them as form options: $user,$globalOption,$location,$designation), $entity, array(
        $form = $this->createForm(new DomainSignType($user,$globalOption,$location,$designation), $entity, array(
            'action' => $this->generateUrl('domain_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Displays a form to create a new DomainUser entity.
     *
     */
    public function newAction()
    {
        $entity = new User();
        $globalOption = $this->getUser()->getGlobalOption();
        $form   = $this->createCreateForm($entity);

        return $this->render('DomainUser/DomainUser/new.html.twig', array(
            'globalOption' => $globalOption,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a DomainUser entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\User\Entity\User::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DomainUser entity.');
        }
        return $this->render('DomainUser/DomainUser/show.html.twig', array(
            'user'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing DomainUser entity.
     *
     */
    public function editAction(User $user)
    {

        $em = $this->getDoctrine()->getManager();
        $editForm = $this->createEditForm($user);
        $globalOption = $user->getGlobalOption();
        return $this->render('DomainUser/DomainUser/edit.html.twig', array(
            'globalOption' => $globalOption,
            'entity'      => $user,
            'form'   => $editForm->createView(),
        ));
    }

    public function inlineUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->getDoctrine()->getManager();

        /* @var $entity User */

        $entity = $em->getRepository(User::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find particular entity.');
        }
        $exist = $em->getRepository(User::class)->findOneBy(array('username' => $data['value']));
        if(empty($exist)){
            $entity->setUsername(strtolower($data['value']));
            $em->flush();
            return new Response('success');
        }
        return new Response('invalid');


    }

    /**
     * Creates a form to edit a DomainUser entity.
     *
     * @param User $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(User $entity)
    {

        $user = $this->getDoctrine()->getRepository(\App\User\Entity\User::class);
        $globalOption = $this->getUser()->getGlobalOption();
        $designation = $this->getDoctrine()->getRepository(\App\Tool\Entity\Designation::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into DomainEditUserType::configureOptions() and pass them as form options: $user,$globalOption,$designation), $entity, array(
        $form = $this->createForm(new DomainEditUserType($user,$globalOption,$designation), $entity, array(
            'action' => $this->generateUrl('domain_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing DomainUser entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /* @var $entity User */
        $entity = $em->getRepository(\App\User\Entity\User::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DomainUser entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if($entity->getProfile()->upload() && !empty($entity->getProfile()->getFile())){
                $entity->getProfile()->removeUpload();
            }
            if($entity->getProfile()->signatureUpload() && !empty($entity->getProfile()->getSignatureFile())){
                $entity->getProfile()->removeSignatureUpload();
            }
            $entity->getProfile()->upload();
            $entity->getProfile()->signatureUpload();
            $em->flush();
            return $this->redirect($this->generateUrl('domain_edit', array('id' => $id)));
        }
        return $this->render('DomainUser/DomainUser/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }

    /**
     * Deletes a DomainUser entity.
     *
     */
    public function deleteAction(User $user)
    {
        $em = $this->getDoctrine()->getManager();
        $user->setIsDelete(true);
        $user->setEnabled(false);
        $em->flush();
        $this->get('session')->getFlashBag()->add(
            'notice',"Data has been deleted successfully"
        );
        return $this->redirect($this->generateUrl('domain_user'));
    }



    /**
     * Creates a form to edit a DomainUser entity.
     *
     * @param User $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditProfileForm(User $entity)
    {

        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->getDoctrine()->getRepository(\App\Location\Entity\Location::class);
        $designation = $this->getDoctrine()->getRepository(\App\Tool\Entity\Designation::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into DomainEditSignType::configureOptions() and pass them as form options: $globalOption,$location,$designation), $entity, array(
        $form = $this->createForm(new DomainEditSignType($globalOption,$location,$designation), $entity, array(
            'action' => $this->generateUrl('domain_update_profile'),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }


    /**
     * Displays a form to edit an existing DomainUser entity.
     *
     */
    public function editProfileAction()
    {

        $user = $this->getUser();
        $editForm = $this->createEditProfileForm($user);
        $globalOption = $user->getGlobalOption();
        return $this->render('DomainUser/DomainUser/profile.html.twig', array(
            'globalOption' => $globalOption,
            'entity'      => $user,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Edits an existing DomainUser entity.
     *
     */
    public function updateProfileAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $this->getUser();

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DomainUser entity.');
        }

        $editForm = $this->createEditProfileForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('domain_edit_profile'));
        }

        return $this->render('DomainUser/DomainUser/profile.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }


    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $go = $this->getUser()->getGlobalOption();
            $item = $this->getDoctrine()->getRepository(\App\User\Entity\User::class)->searchAutoComplete($item,$go);
        }
        return new JsonResponse($item);
    }

    public function autoSearchProfileNameAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $go = $this->getUser()->getGlobalOption();
            $item = $this->getDoctrine()->getRepository(\App\User\Entity\User::class)->searchAutoCompleteProfile($item,$go);
        }
        return new JsonResponse($item);
    }

    public function searchUserNameAction($user)
    {
        return new JsonResponse(array(
            'id' => $user,
            'text' => $user
        ));
    }

    public function forgetPasswordAction(User $user)
    {
        $password = '@123456';
        $user->setPlainPassword($password);
        $user->setAppPassword($password);
        $this->get('fos_user.user_manager')->updateUser($user);
        $this->get('session')->getFlashBag()->add(
            'success',"Password reset successfully"
        );
      //  $dispatcher = $this->container->get('event_dispatcher');
      //  $dispatcher->dispatch('setting_tool.post.change_password', new \App\Tool\Event\PasswordChangeSmsEvent($user,$password));
        return $this->redirect($this->generateUrl('domain_user'));
    }

  

}
