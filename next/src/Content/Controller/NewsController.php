<?php

namespace App\Content\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Content\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Content\Entity\News;
use App\Content\Form\NewsType;

/**
 * News controller.
 *
 */
class NewsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists Spacific user Page entities.
     *
     */
    public function indexAction()
    {

        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Content\Entity\Page::class)->getPagesFor($globalOption,'news');

        return $this->render('Content/News/index.html.twig', array(
            'pagination' => $entities,
        ));
    }

    /**
     * Creates a new Page entity.
     *
     */
    public function createAction(Request $request)
    {

        $user = $this->getUser();
        $entity = new Page();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity ->setUser($user);
            $entity ->setModule($this->em->getRepository(\App\Tool\Entity\Module::class)->findOneBy(array('slug' => 'news')));
            $entity ->setGlobalOption($user->getGlobalOption());
            $entity->upload();
            $em->persist($entity);
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );

            return $this->redirect($this->generateUrl('news'));
        }

        return $this->render('Content/News/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Page entity.
     *
     * @param Page $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Page $entity)
    {

        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(NewsType::class, $entity, array('action' => $this->generateUrl('news_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
        return $form;

    }

    /**
     * Displays a form to create a new Page entity.
     *
     */
    public function newAction()
    {
        $entity = new Page();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/News/new.html.twig', array(
            'entity' => $entity,
            'globalOption' => $this->getUser()->getGlobalOption(),
            'form'   => $form->createView(),
        ));

    }

    /**
     * Finds and displays a Page entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find News entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/News/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Page entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find News entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/News/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Page entity.
    *
    * @param Page $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Page $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();

        $form = $this->createForm(NewsType::class, $entity, array('action' => $this->generateUrl('news_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));
       return $form;
    }
    /**
     * Edits an existing Page entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find News entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            if( $entity->upload() and !empty($entity->getFile())){
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );

            return $this->redirect($this->generateUrl('news_edit', array('id' => $id)));
        }

        return $this->render('Content/News/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Page entity.
     *
     */
    public function deleteAction(Page $entity)
    {
        $em = $this->em;
        if(!empty($entity->getFile())){
            $entity->removeUpload();
        }
        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add('success',"Data has been updated successfully");
        return $this->redirect($this->generateUrl('news'));
    }

    /**
     * Creates a form to delete a Page entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('news_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * Status a news entity.
     *
     */
    public function statusAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        $em = $this->em;
        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

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
        return $this->redirect($this->generateUrl('news'));
    }

}
