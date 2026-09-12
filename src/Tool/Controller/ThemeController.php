<?php

namespace App\Tool\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Tool\Entity\Theme;
use App\Tool\Form\ThemeType;

/**
 * Theme controller.
 *
 */
class ThemeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Theme entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\Theme::class)->findAll();

        return $this->render('Tool/Theme/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new Theme entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Theme();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            //$this->createThemeFolder($entity->getFolderName());
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('theme_show', ['id' => $entity->getId()]));
        }

        return $this->render('Tool/Theme/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    public function createThemeFolder($folderName)
    {
        $mobile_dir = __DIR__.'/../../../../../src/Frontend/FrontendBundle/Resources/views/Template/Desktop/';
        $desktop_dir = __DIR__.'/../../../../../src/Frontend/FrontendBundle/Resources/views/Template/Mobile/';
        if(!file_exists($mobile_dir)){
                mkdir($mobile_dir.'/'.$folderName, 0777);
                mkdir($desktop_dir.'/'.$folderName, 0777);
            }else{
                return false;

        }


    }

    /**
     * Creates a form to create a Theme entity.
     *
     * @param Theme $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Theme $entity)
    {
        $form = $this->createForm(ThemeType::class, $entity, [
            'action' => $this->generateUrl('theme_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new Theme entity.
     *
     */
    public function newAction()
    {
        $entity = new Theme();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/Theme/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a Theme entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\Theme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Theme entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Theme/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Theme entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\Theme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Theme entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/Theme/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Theme entity.
     *
     * @param Theme $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Theme $entity)
    {
        $form = $this->createForm(ThemeType::class, $entity, [
            'action' => $this->generateUrl('theme_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
       return $form;
    }

    /**
     * Edits an existing Theme entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\Theme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Theme entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $entity->upload();
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('theme_edit', ['id' => $id]));
        }

        return $this->render('Tool/Theme/new.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a Theme entity.
     *
     */
    public function deleteAction(Theme $entity)
    {
            $em = $this->em;
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Theme entity.');
            }
            $entity->removeUpload();
            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );
            return $this->redirect($this->generateUrl('theme'));
    }

    /**
     * Creates a form to delete a Theme entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('theme_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
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
        $entity = $em->getRepository(\App\Tool\Entity\Theme::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(false);
        } else{
            $entity->setStatus(true);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('theme'));
    }

}
