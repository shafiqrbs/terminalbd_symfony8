<?php

namespace App\Content\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use App\Content\Entity\ModuleCategory;
use App\Content\Form\ModuleCategoryType;

/**
 * ModuleCategory controller.
 *
 */
class ModuleCategoryController extends Controller
{

    /**
     * Lists all ModuleCategory entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Content\Entity\ModuleCategory::class)->findBy(array('globalOption'=>$globalOption));

        return $this->render('Content/ModuleCategory/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new ModuleCategory entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ModuleCategory();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();
            $entity->setGlobalOption($user->getGlobalOption());
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Appearance\Entity\Menu::class)->createMenuForCategory($entity);
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been insrted successfully"
            );
            return $this->redirect($this->generateUrl('modulecategory'));
        }

        return $this->render('Content/ModuleCategory/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a ModuleCategory entity.
     *
     * @param ModuleCategory $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ModuleCategory $entity)
    {

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into ModuleCategoryType::configureOptions() and pass them as form options: $this->getUser()->getGlobalOption()), $entity, array(
        $form = $this->createForm(new ModuleCategoryType($this->getUser()->getGlobalOption()), $entity, array(
            'action' => $this->generateUrl('modulecategory_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Displays a form to create a new ModuleCategory entity.
     *
     */
    public function newAction()
    {
        $entity = new ModuleCategory();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/ModuleCategory/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a ModuleCategory entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Content\Entity\ModuleCategory::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ModuleCategory entity.');
        }
        return $this->render('Content/ModuleCategory/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing ModuleCategory entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Content\Entity\ModuleCategory::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ModuleCategory entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Content/ModuleCategory/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }


    /**
     * Creates a form to edit a ModuleCategory entity.
     *
     * @param ModuleCategory $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(ModuleCategory $entity)
    {

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into ModuleCategoryType::configureOptions() and pass them as form options: $this->getUser()->getGlobalOption()), $entity, array(
        $form = $this->createForm(new ModuleCategoryType($this->getUser()->getGlobalOption()), $entity, array(
            'action' => $this->generateUrl('modulecategory_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing ModuleCategory entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Content\Entity\ModuleCategory::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ModuleCategory entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if($entity->upload() && !empty($entity->getFile())){
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            $this->getDoctrine()->getRepository(\App\Appearance\Entity\Menu::class)->updateMenuForCategory($entity);
            return $this->redirect($this->generateUrl('modulecategory_edit', array('id' => $id)));
        }

        return $this->render('Content/ModuleCategory/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }


    /**
     * Deletes a ModuleCategory entity.
     *
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Content\Entity\ModuleCategory::class)->findOneBy(array('globalOption'=>$globalOption,'id'=>$id));
        if (!empty($entity)) {
            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Status has been deleted successfully"
            );
        }else{
            $this->get('session')->getFlashBag()->add(
                'error',"Sorry! Data not deleted"
            );
        }
        return $this->redirect($this->generateUrl('modulecategory'));
    }

    

    /**
     * Status a news entity.
     *
     */
    public function statusAction(Request $request, $id)
    {



        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Content\Entity\ModuleCategory::class)->find($id);

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
        $this->get('session')->getFlashBag()->add(
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('modulecategory'));
    }

 
}
