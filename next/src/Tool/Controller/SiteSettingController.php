<?php

namespace App\Tool\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Tool\Entity\SiteSetting;
use App\Tool\Entity\GlobalOption;
use App\Tool\Form\SiteSettingType;

/**
 * SiteSetting controller.
 *
 */
class SiteSettingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    /**
     * Lists all SiteSetting entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\SiteSetting::class)->findAll();
        $paginate = $this->paginate($entities);
        return $this->render('Tool/SiteSetting/index.html.twig', array(
            'pagination' => $paginate,
        ));
    }


    public function paginate($entities)
    {

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            30  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Creates a new SiteSetting entity.
     *
     */
    public function createAction(Request $request)
    {
        return $this->redirect($this->generateUrl('sitesetting'));
    }

    /**
     * Creates a form to create a SiteSetting entity.
     *
     * @param SiteSetting $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(SiteSetting $entity)
    {
        $user = $this->getUser();
        $em = $this->em;
        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->findOneBy(array('user' => $user));
        if($entities){
            $syndicateId = $entities->getSyndicate()->getId();
        }

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into SiteSettingType::configureOptions() and pass them as form options: $syndicateId), $entity, array(
        $form = $this->createForm(new SiteSettingType($syndicateId), $entity, array(
            'action' => $this->generateUrl('sitesetting_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));


        return $form;
    }

    /**
     * Displays a form to create a new SiteSetting entity.
     *
     */
    public function newAction()
    {

        $entity = new SiteSetting();
        $form   = $this->createCreateForm($entity);

        return $this->render('Tool/SiteSetting/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a SiteSetting entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SiteSetting::class)->find($id);


        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteSetting entity.');

        }

        return $this->render('Tool/SiteSetting/show.html.twig', array(
            'entity'      => $entity
        ));
    }

    /**
     * Displays a form to edit an existing SiteSetting entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SiteSetting::class)->find($id);

        $editForm = $this->createEditForm($entity);

        return $this->render('Tool/SiteSetting/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a SiteSetting entity.
    *
    * @param SiteSetting $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(SiteSetting $entity)
    {

        $em = $this->em;
        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->findOneBy(array('user' => $entity->getUser()));
        if($entities){
            $syndicateId = $entities->getSyndicate()->getId();
        }

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into SiteSettingType::configureOptions() and pass them as form options: $syndicateId), $entity, array(
        $form = $this->createForm(new SiteSettingType($syndicateId), $entity, array(
            'action' => $this->generateUrl('sitesetting_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));

        return $form;
    }
    /**
     * Edits an existing SiteSetting entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\SiteSetting::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteSetting entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
        }
        $this->em->getRepository(\App\Tool\Entity\SiteSetting::class)->updateSettingMenu($entity);

        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }
    /**
     * Deletes a SiteSetting entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Tool\Entity\SiteSetting::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SiteSetting entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sitesetting'));
    }

    /**
     * Creates a form to delete a SiteSetting entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('sitesetting_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * Displays a form to edit an existing SiteSetting entity.
     *
     */
    public function modifyAction()
    {
        $em = $this->em;

        $user = $this->getUser();

        $entity = $em->getRepository(\App\Tool\Entity\SiteSetting::class)->findOneBy(array('user'=>$user));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SiteSetting entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Tool/SiteSetting/modify.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

}
