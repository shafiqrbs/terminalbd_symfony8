<?php

namespace App\Tool\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Tool\Entity\SmsBulk;
use App\Tool\Form\SmsBulkType;

/**
 * SmsBulk controller.
 *
 */
class SmsBulkController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all SmsBulk entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\SmsBulk::class)->findAll();

        return $this->render('Tool/SmsBulk/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new SmsBulk entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new SmsBulk();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('smsbulk_show', array('id' => $entity->getId())));
        }

        return $this->render('Tool/SmsBulk/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    public function createSmsBulkFolder($folderName)
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
     * Creates a form to create a SmsBulk entity.
     *
     * @param SmsBulk $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(SmsBulk $entity)
    {

        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(SmsBulkType::class, $entity, array('action' => $this->generateUrl('smsbulk_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'location' => $location));
        return $form;
    }

    /**
     * Displays a form to create a new SmsBulk entity.
     *
     */
    public function newAction()
    {
        $entity = new SmsBulk();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/SmsBulk/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a SmsBulk entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SmsBulk::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SmsBulk entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/SmsBulk/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing SmsBulk entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SmsBulk::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SmsBulk entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/SmsBulk/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a SmsBulk entity.
    *
    * @param SmsBulk $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(SmsBulk $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(SmsBulkType::class, $entity, array('action' => $this->generateUrl('smsbulk_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'location' => $location));
       return $form;
    }
    /**
     * Edits an existing SmsBulk entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SmsBulk::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SmsBulk entity.');
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
            return $this->redirect($this->generateUrl('smsbulk_edit', array('id' => $id)));
        }

        return $this->render('Tool/SmsBulk/new.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a SmsBulk entity.
     *
     */
    public function deleteAction(SmsBulk $entity)
    {
            $em = $this->em;
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SmsBulk entity.');
            }
            $entity->removeUpload();
            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );
            return $this->redirect($this->generateUrl('smsbulk'));
    }

    /**
     * Creates a form to delete a SmsBulk entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('smsbulk_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
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
        $entity = $em->getRepository(\App\Tool\Entity\SmsBulk::class)->find($id);

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
        return $this->redirect($this->generateUrl('smsbulk'));
    }

    public function sendingAction(SmsBulk $entity)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        /* @var $global GlobalOption */
        $global = $this->getUser()->getGlobalOption();
        if ($global->getSmsSenderTotal() and $global->getSmsSenderTotal()->getRemaining() > 0 and $global->getNotificationConfig()->getSmsActive() == 1) {
            if ($entity->getProcess() != "Complete") {
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->dispatch(new \App\Tool\Event\SmsBulkEvent($entity), 'setting_tool.post.bulk_sms');
                $entity->setProcess('Complete');
                $em->flush();
            }
        }
        return $this->redirect($this->generateUrl('smsbulk_show',array('id'=>$entity->getId())));
    }

}
