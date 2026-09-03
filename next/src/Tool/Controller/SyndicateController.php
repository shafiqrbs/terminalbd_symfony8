<?php

namespace App\Tool\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Tool\Entity\Syndicate;
use App\Tool\Form\SyndicateType;

/**
 * Syndicate controller.
 *
 */
class SyndicateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Syndicate entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\Syndicate::class)->findAll();

        return $this->render('Tool/Syndicate/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Syndicate entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Syndicate();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $em = $this->em;
            $em->persist($entity);

            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('syndicate_new'));
            //return $this->redirect($this->generateUrl('syndicate_show', array('id' => $entity->getId())));
        }

        return $this->render('Tool/Syndicate/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Syndicate entity.
     *
     * @param Syndicate $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Syndicate $entity)
    {

        $em = $this->em->getRepository(\App\Tool\Entity\Syndicate::class);

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into SyndicateType::configureOptions() and pass them as form options: $em), $entity, array(
        $form = $this->createForm(new SyndicateType($em), $entity, array(
            'action' => $this->generateUrl('syndicate_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));


        return $form;
    }

    /**
     * Displays a form to create a new Syndicate entity.
     *
     */
    public function newAction()
    {
        $entity = new Syndicate();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/Syndicate/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Syndicate entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\Syndicate::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Syndicate entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Syndicate/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Syndicate entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\Syndicate::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Syndicate entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Syndicate/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Syndicate entity.
    *
    * @param Syndicate $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Syndicate $entity)
    {

        $em = $this->em->getRepository(\App\Tool\Entity\Syndicate::class);

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into SyndicateType::configureOptions() and pass them as form options: $em), $entity, array(
        $form = $this->createForm(new SyndicateType($em), $entity, array(
            'action' => $this->generateUrl('syndicate_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));

        return $form;
    }
    /**
     * Edits an existing Syndicate entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\Syndicate::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Syndicate entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {

            $menu = $entity ->getName();
            $em->persist($entity);

            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('syndicate'));
        }

        return $this->render('Tool/Syndicate/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Syndicate entity.
     *
     */
    public function deleteAction(Syndicate $entity)
    {
            $em = $this->em;
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Syndicate entity.');
            }

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );
            return $this->redirect($this->generateUrl('syndicate'));
    }

    /**
     * Creates a form to delete a Syndicate entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('syndicate_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        //$data = $request->request->all();


        $em = $this->em;
        $entity = $em->getRepository(\App\Tool\Entity\Syndicate::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('syndicate'));
    }

}
