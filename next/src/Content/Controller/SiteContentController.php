<?php

namespace App\Content\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Appearance\Service\MenuManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Content\Entity\SiteContent;
use App\Content\Form\SiteContentType;

/**
 * SiteContent controller.
 *
 */
class SiteContentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MenuManager $menuManager,
    ) {
    }


    /**
     * Lists all SiteContent entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Content\Entity\SiteContent::class)->findAll();

        return $this->render('Content/SiteContent/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new SiteContent entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SiteContent();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $slug = $this->menuManager-> urlSlug($entity->getName());
            $entity ->setSlug($slug);
            $entity ->upload();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('sitecontent'));
        }

        return $this->render('Content/SiteContent/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a SiteContent entity.
     *
     * @param SiteContent $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(SiteContent $entity)
    {
	    $form = $this->createForm(SiteContentType::class, $entity, array('action' => $this->generateUrl('sitecontent_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $this->getUser()->getGlobalOption()));
	    return $form;
    }

    /**
     * Displays a form to create a new SiteContent entity.
     *
     */
    public function newAction()
    {
        $entity = new SiteContent();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/SiteContent/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a SiteContent entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SiteContent::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteContent entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/SiteContent/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing SiteContent entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SiteContent::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteContent entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/SiteContent/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a SiteContent entity.
    *
    * @param SiteContent $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(SiteContent $entity)
    {
        $form = $this->createForm(SiteContentType::class, $entity, array('action' => $this->generateUrl('sitecontent_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $this->getUser()->getGlobalOption()));
        return $form;
    }
    /**
     * Edits an existing SiteContent entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SiteContent::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteContent entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $slug = $this->menuManager->urlSlug($entity->getName());
            $entity ->setSlug($slug);
            $entity->upload();
            $em->flush();

            return $this->redirect($this->generateUrl('sitecontent'));
        }

        return $this->render('Content/SiteContent/new.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a SiteContent entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Content\Entity\SiteContent::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SiteContent entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sitecontent'));
    }

    /**
     * Creates a form to delete a SiteContent entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sitecontent_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
