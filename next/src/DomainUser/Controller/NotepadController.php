<?php

namespace App\DomainUser\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\DomainUser\Entity\Notepad;
use App\DomainUser\Form\NotepadType;

/**
 * Notepad controller.
 *
 */
class NotepadController extends Controller
{


    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }


    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = $_REQUEST;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\DomainUser\Entity\Notepad::class)->findWithSearch($globalOption,$data);
        $pagination = $this->paginate($entities);
		$notepadID = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Notepad::class)->generateNotepad($globalOption);
	    $entity = $em->getRepository(\App\DomainUser\Entity\Notepad::class)->findOneBy(array('globalOption'=>$globalOption,'id' => $notepadID));

	    return $this->render('DomainUser/Notepad/index.html.twig', array(
            'entities' => $pagination,
            'searchForm' => $data,
            'notepad' => $entity,
        ));
    }

    /**
     * Creates a new Notepad entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Notepad();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $globalOption = $this->getUser()->getGlobalOption();
            $entity->setGlobalOption($globalOption);
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('domain_notepad'));
        }

        return $this->render('DomainUser/Notepad/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Notepad entity.
     *
     * @param Notepad $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Notepad $entity)
    {
        $location = $this->getDoctrine()->getRepository(\App\Location\Entity\Location::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into NotepadType::configureOptions() and pass them as form options: $location), $entity, array(
        $form = $this->createForm(new NotepadType($location), $entity, array(
            'action' => $this->generateUrl('domain_notepad_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Displays a form to create a new Notepad entity.
     *
     */

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $entity = new Notepad();
        $form   = $this->createCreateForm($entity);

        return $this->render('DomainUser/Notepad/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Notepad entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\DomainUser\Entity\Notepad::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Notepad entity.');
        }

        return $this->render('DomainUser/Notepad/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing Notepad entity.
     *
     */

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\DomainUser\Entity\Notepad::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Notepad entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('DomainUser/Notepad/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

     public function insertAction($id)
    {
        $em = $this->getDoctrine()->getManager();
		$notes = $_REQUEST['notes'];
        $entity = $em->getRepository(\App\DomainUser\Entity\Notepad::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Notepad entity.');
        }

        $entity->setContent($notes);
        $em->flush();
        exit;


    }

    /**
    * Creates a form to edit a Notepad entity.
    *
    * @param Notepad $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Notepad $entity)
    {
        $location = $this->getDoctrine()->getRepository(\App\Location\Entity\Location::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into NotepadType::configureOptions() and pass them as form options: $location), $entity, array(
        $form = $this->createForm(new NotepadType($location), $entity, array(
            'action' => $this->generateUrl('domain_notepad_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing Notepad entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\DomainUser\Entity\Notepad::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Notepad entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            return $this->redirect($this->generateUrl('domain_notepad'));
        }

        return $this->render('DomainUser/Notepad/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a Notepad entity.
     *
     */

    #[IsGranted(new Expression("is_granted('ROLE_CRM') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\DomainUser\Entity\Notepad::class)->findOneBy(array('globalOption'=>$globalOption,'id' => $id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Notepad entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->get('session')->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }
        exit;

        return $this->redirect($this->generateUrl('domain_notepad'));
    }


}
