<?php

namespace App\Content\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Appearance\Service\MenuManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\SiteTeam;
use App\Content\Form\SiteTeamType;

/**
 * SiteTeam controller.
 *
 */
class SiteTeamController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MenuManager $menuManager,
    ) {
    }


    /**
     * Lists all SiteTeam entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Content\Entity\SiteTeam::class)->findAll();

        return $this->render('Content/SiteTeam/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new SiteTeam entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SiteTeam();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $slug = $this->menuManager-> urlSlug($entity->getName());
            $entity ->setSlug($slug);
            $entity ->upload();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('siteteam'));
        }

        return $this->render('Content/SiteTeam/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a SiteTeam entity.
     *
     * @param SiteTeam $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(SiteTeam $entity)
    {
	    $form = $this->createForm(SiteTeamType::class, $entity, [
            'action' => $this->generateUrl('siteteam_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
	    return $form;
    }

    /**
     * Displays a form to create a new SiteTeam entity.
     *
     */
    public function newAction()
    {
        $entity = new SiteTeam();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/SiteTeam/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a SiteTeam entity.
     *
     */
    public function showAction($id)
    {
    }

    /**
     * Displays a form to edit an existing SiteTeam entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SiteTeam::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteTeam entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Content/SiteTeam/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a SiteTeam entity.
     *
     * @param SiteTeam $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(SiteTeam $entity)
    {
        $form = $this->createForm(SiteTeamType::class, $entity, [
            'action' => $this->generateUrl('siteteam_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing SiteTeam entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\SiteTeam::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteTeam entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $slug = $this->menuManager->urlSlug($entity->getName());
            $entity ->setSlug($slug);
            $entity->upload();
            $em->flush();

            return $this->redirect($this->generateUrl('siteteam'));
        }

        return $this->render('Content/SiteTeam/new.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a SiteTeam entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Content\Entity\SiteTeam::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SiteTeam entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('siteteam'));
    }

    /**
     * Creates a form to delete a SiteTeam entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('siteteam_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
