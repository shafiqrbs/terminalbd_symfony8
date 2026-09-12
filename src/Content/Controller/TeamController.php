<?php

namespace App\Content\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Content\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Content\Entity\Team;
use App\Content\Form\TeamType;

/**
 * Team controller.
 *
 */
class TeamController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            20  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Lists all Team entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Content\Entity\Page::class)->getPagesFor($globalOption,'team');
        $entities = $this->paginate($entities);

        return $this->render('Content/Team/index.html.twig', array(
            'pagination' => $entities,
        ));
    }
    /**
     * Creates a new Team entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Page();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        $user = $this->getUser();
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setUser($user);
            $entity->setGlobalOption($user->getGlobalOption());
            $entity ->setModule($this->em->getRepository(\App\Tool\Entity\Module::class)->findOneBy(array('slug' => 'team')));
            $entity->upload();
            $em->persist($entity);
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('team'));
        }
        return $this->render('Content/Team/new.html.twig', array(
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

        $globalOption = $this->getUser()->getGlobalOption()->getId();
        $form = $this->createForm(TeamType::class, $entity, array('action' => $this->generateUrl('team_create', array('id' => $entity->getId())),
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

        return $this->render('Content/Team/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Team entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingContentBundle:Team')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Team entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Content/Team/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Team entity.
     *
     */
    public function editAction(Page $entity )
    {
        $em = $this->em;
        $editForm = $this->createEditForm($entity);

        return $this->render('Content/Team/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }

    /**
     * Creates a form to edit a Page entity.
     *
     * @param Team $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Page $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption()->getId();
        $form = $this->createForm(TeamType::class, $entity, array('action' => $this->generateUrl('team_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'globalOption' => $globalOption));

        return $form;
    }
    /**
     * Edits an existing Team entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Team entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            if( $entity->upload() and !empty($entity->getFile())){
                $entity->removeUpload();
            }
            $entity->upload();
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add('success',"Data has been updated successfully");
            return $this->redirect($this->generateUrl('team_edit', array('id' => $id)));
        }

        return $this->render('Content/Team/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
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
        $this->requestStack->getSession()->getFlashBag()->add('success',"Data has been deleted successfully");
        return $this->redirect($this->generateUrl('team'));
    }

    /**
     * Creates a form to delete a Team entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('team_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
            ;
    }

    /**
     * Status a Team entity.
     *
     */
    public function statusAction(Request $request, Page $entity)
    {

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
        return $this->redirect($this->generateUrl('team'));
    }
}
