<?php

namespace App\Content\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\Blackout;
use App\Content\Form\BlackoutType;

/**
 * Blackout controller.
 *
 */
class BlackoutController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all Blackout entities.
     *
     */
    public function indexAction()
    {

        $user = $this->getUser();
        $em = $this->em;

        $entity = $em->getRepository('SettingContentBundle:Blackout')->findOneBy(['user'=>$user]);

        if (!empty($entity)) {

            $id = $entity->getId();
            return $this->redirect($this->generateUrl('blackout_edit',['id'=>$id]));

        }else{

            return $this->redirect($this->generateUrl('blackout_new'));
        }


    }
    /**
     * Creates a new Blackout entity.
     *
     */
    public function createAction(Request $request)
    {
        $user = $this->getUser();
        $entity = new Blackout();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blackout entity.');
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->em;
            $entity->setUser($user);
            $em->persist($entity);
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
        }
        return $this->redirect($this->generateUrl('blackout'));

    }

    /**
     * Creates a form to create a Blackout entity.
     *
     * @param Blackout $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Blackout $entity)
    {

        $form = $this->createForm(new BlackoutType(), $entity , [
            'action' => $this->generateUrl('blackout_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);

        $form->add('submit', 'submit', ['label' => 'Submit', 'attr' => ['class' => 'btn btn-primary']]);
        return $form;
    }

    /**
     * Displays a form to create a new Blackout entity.
     *
     */
    public function newAction()
    {
        $entity = new Blackout();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/Blackout/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }


    /**
     * Displays a form to edit an existing Blackout entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $user = $this->getUser();
        $entity = $em->getRepository('SettingContentBundle:Blackout')->findOneBy(['id'=> $id ,'user'=>$user]);

        if (!$entity) {

             return $this->redirect($this->generateUrl('blackout'));
        }

        $editForm = $this->createEditForm($entity);

        $blackOutDate =  $entity ->getBlackOutDate();
        $blackoutdate = $blackOutDate ?? '';
        $blackoutdate= explode(',' , $blackOutDate);

        $lastdate = end($blackoutdate);


        return $this->render('Content/Blackout/edit.html.twig', [

            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'blackOutDate' => $blackoutdate,
            'lastdate' => $lastdate

        ]);
    }

    /**
     * Creates a form to edit a Blackout entity.
     *
     * @param Blackout $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Blackout $entity)
    {

        $form = $this->createForm(new BlackoutType(), $entity, [
            'action' => $this->generateUrl('blackout_update', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);

        $form->add('submit', 'submit', ['label' => 'Submit', 'attr' => ['class' => 'btn btn-primary']]);
        return $form;
    }
    /**
     * Edits an existing Blackout entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $user = $this->getUser();
        $entity = $em->getRepository('SettingContentBundle:Blackout')->findOneBy(['id'=> $id ,'user'=>$user]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blackout entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
        }

        return $this->redirect($this->generateUrl('blackout_edit', ['id' => $id]));

    }

}
