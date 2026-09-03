<?php

namespace App\Content\Controller;

use App\Appearance\Service\MenuManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Content\Entity\SyndicateContent;
use App\Content\Form\SyndicateContentType;

/**
 * SyndicateContent controller.
 *
 */
class SyndicateContentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly MenuManager $menuManager,
    ) {
    }


    /**
     * Lists all SyndicateContent entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Content\Entity\SyndicateContent::class)->findAll();
        $user = $this->getUser();
        return $this->render('Content/SyndicateContent/index.html.twig', array(
            'user' => $user,
        ));
    }
    /**
     * Creates a new SyndicateContent entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SyndicateContent();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity->setSlug($this->menuManager->urlSlug($entity->getName()));
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('syndicatecontent_show', array('id' => $entity->getId())));
        }

        return $this->render('Content/SyndicateContent/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a SyndicateContent entity.
     *
     * @param SyndicateContent $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(SyndicateContent $entity)
    {

        $globalOption = $this->getUser()->getGlobalOption()->getId();
        $siteSettingId = $entity->getUser()->getSiteSetting()->getId();

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into SyndicateContentType::configureOptions() and pass them as form options: $globalOption,$siteSettingId), $entity, array(
        $form = $this->createForm(new SyndicateContentType($globalOption,$siteSettingId), $entity, array(
            'action' => $this->generateUrl('syndicatecontent_create'),
            'method' => 'POT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            )
        ));

        $form->add('submit', 'submit', array('label' => 'Update', 'attr' => array('class' => 'btn btn-primary')));
        $form->add('reset', 'reset', array('label' => 'Reset', 'attr' => array('class' => 'btn btn-danger')));

        return $form;
    }

    /**
     * Displays a form to create a new SyndicateContent entity.
     *
     */
    public function newAction()
    {
        $entity = new SyndicateContent();


        $form   = $this->createCreateForm($entity);

        return $this->render('Content/SyndicateContent/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a SyndicateContent entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SyndicateContent::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SyndicateContent entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/SyndicateContent/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing SyndicateContent entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SyndicateContent::class)->findOneBy(array('syndicate'=>$id));

        if (!$entity) {
            $user = $this->getUser();
            $this->em->getRepository(\App\Content\Entity\SyndicateContent::class)->insertContent($id,$user);
            $entity = $em->getRepository(\App\Content\Entity\SyndicateContent::class)->findOneBy(array('syndicate'=>$id));

        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Content/SyndicateContent/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a SyndicateContent entity.
    *
    * @param SyndicateContent $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(SyndicateContent $entity)
    {
       $globalOption = $this->getUser()->getGlobalOption()->getId();
       $siteSettingId = $this->getUser()->getSiteSetting()->getId();
            // TODO(migration): SF3+ takes a form type class-string, not an instance.
            // Move these constructor arguments into SyndicateContentType::configureOptions() and pass them as form options: $globalOption,$siteSettingId), $entity, array(
            $form = $this->createForm(new SyndicateContentType($globalOption,$siteSettingId), $entity, array(
            'action' => $this->generateUrl('syndicatecontent_update', array('id' => $entity->getId())),
            'method' => 'PUT',
                'attr' => array(
                    'class' => 'form-horizontal',
                    'novalidate' => 'novalidate',
                )
        ));

        $form->add('submit', 'submit', array('label' => 'Update', 'attr' => array('class' => 'btn btn-primary')));
        $form->add('reset', 'reset', array('label' => 'Reset', 'attr' => array('class' => 'btn btn-danger')));

        return $form;

    }
    /**
     * Edits an existing SyndicateContent entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SyndicateContent::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SyndicateContent entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->setSlug($this->menuManager->urlSlug($entity->getName()));
            $entity->upload();
            $em->flush();
            $syndicateId = $entity->getSyndicate()->getId();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );

            return $this->redirect($this->generateUrl('syndicatecontent_edit', array('id' => $syndicateId)));
        }

        return $this->render('Content/SyndicateContent/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a SyndicateContent entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Content\Entity\SyndicateContent::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SyndicateContent entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('syndicatecontent'));
    }

    /**
     * Creates a form to delete a SyndicateContent entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('syndicatecontent_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
