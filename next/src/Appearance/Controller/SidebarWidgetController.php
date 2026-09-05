<?php

namespace App\Appearance\Controller;


use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Appearance\Form\SidebarWidgetPanelType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Appearance\Entity\SidebarWidget;
use App\Appearance\Form\SidebarWidgetType;

/**
 * SidebarWidget controller.
 *
 */
class SidebarWidgetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all SidebarWidget entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Appearance\Entity\SidebarWidget::class)->findBy(array());
        return $this->render('Appearance/SidebarWidget/index.html.twig', array(
            'entities' => $entities,
        ));
    }


    /**
     * Creates a new SidebarWidget entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SidebarWidget();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('sidebarwidget_edit', array('id' => $entity->getId())));
        }

        return $this->render('Appearance/SidebarWidget/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a SidebarWidget entity.
     *
     * @param SidebarWidget $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(SidebarWidget $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $em = $this->em->getRepository(\App\Product\Entity\Category::class);
        $form = $this->createForm(SidebarWidgetType::class, $entity, array('action' => $this->generateUrl('sidebarwidget_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }

    /**
     * Displays a form to create a new SidebarWidget entity.
     *
     */
    public function newAction()
    {
        $entity = new SidebarWidget();
        $form   = $this->createCreateForm($entity);
        return $this->render('Appearance/SidebarWidget/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Displays a form to edit an existing SidebarWidget entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Appearance\Entity\SidebarWidget::class)->find($id);
        $option = $this->getUser()->getGlobalOption();
        $sidebarWidgetPanel = $em->getRepository(\App\Appearance\Entity\SidebarWidgetPanel::class)->findOneBy(array('globalOption' => $option,'sidebarWidget' =>$entity));
        if(empty($sidebarWidgetPanel)){

            $sidebarPanel = new SidebarWidgetPanel();
            $sidebarPanel->setGlobalOption($option);
            $sidebarPanel->setSidebarWidget($entity);
            $em->persist($sidebarPanel);
            $em->flush();
            $panel = $sidebarPanel;
        }else{
            $panel = $sidebarWidgetPanel;
        }



        $editForm = $this->createEditForm($panel);

        return $this->render('Appearance/SidebarWidget/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a SidebarWidget entity.
     *
     * @param SidebarWidget $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(SidebarWidgetPanel $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(SidebarWidgetPanelType::class, $entity, array('action' => $this->generateUrl('sidebarwidget_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;
    }
    /**
     * Edits an existing SidebarWidget entity.
     *
     */
    public function updateAction(Request $request, SidebarWidgetPanel $entity)
    {
        $em = $this->em;



        $deleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('sidebarwidget_edit', array('id' => $entity->getSidebarWidget()->getId())));
        }

        return $this->render('Appearance/SidebarWidget/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a SidebarWidget entity.
     *
     */
    public function deleteAction(SidebarWidget $megamenu )
    {

            $em = $this->em;
            if (!$megamenu) {
                throw $this->createNotFoundException('Unable to find SidebarWidget entity.');
            }

            $em->remove($megamenu);
            $em->flush();
            return $this->redirect($this->generateUrl('megamenu'));
    }

    /**
     * Creates a form to delete a Admission entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sidebarwidget_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
            ;
    }




    public function sortedAction(Request $request){

        $data = $request->request->get('menuItem');
        $this->em->getRepository(\App\Appearance\Entity\SidebarWidget::class)->setOrdering($data);
        exit;

    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, Page $entity)
    {
        $form = $this->createDeleteForm($entity->getId());
        $form->handleRequest($request);

        $em = $this->em;
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
        return $this->redirect($this->generateUrl('megamenu'));
    }


}
