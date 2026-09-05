<?php

namespace App\Content\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\MallConnect;
use App\Content\Form\MallConnectType;

/**
 * MallConnect controller.
 *
 */
class MallConnectController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all MallConnect entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository('SettingContentBundle:MallConnect')->findBy(['globalOption'=>$globalOption]);
        return $this->render('Content/MallConnect/index.html.twig', [
            'entities' => $entities,
        ]);


    }

    /**
     * Creates a new MallConnect entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MallConnect();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if($entity->getShopNo())

        if ($form->isSubmitted() && $form->isValid() and $this->checkDuplicateEntry($entity) == true) {

                $em = $this->em;
                $globalOption = $this->getUser()->getGlobalOption();
                $entity->setGlobalOption($globalOption);
                $entity->setName($globalOption->getName());
                $em->persist($entity);
                $em->flush();
                return $this->redirect($this->generateUrl('mallconnect'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"This data is already in use"
        );
        return $this->render('Content/MallConnect/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    public function checkDuplicateEntry(MallConnect $entity)
    {
        $em = $this->em;
        $true = $em->getRepository('SettingContentBundle:MallConnect')->findBy(['shopNo'=>$entity->getShopNo(),'mall'=>$entity->getMall()]);
        if(!empty($true))
        {
            return false;
        }
        return true;
    }

    /**
     * Creates a form to create a MallConnect entity.
     *
     * @param MallConnect $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MallConnect $entity)
    {
        $form = $this->createForm(MallConnectType::class, $entity, [
            'action' => $this->generateUrl('mallconnect_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new MallConnect entity.
     *
     */
    public function newAction()
    {
        $entity = new MallConnect();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/MallConnect/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a MallConnect entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingContentBundle:MallConnect')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MallConnect entity.');
        }

        return $this->render('Content/MallConnect/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    /**
     * Displays a form to edit an existing MallConnect entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingContentBundle:MallConnect')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MallConnect entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Content/MallConnect/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a MallConnect entity.
     *
     * @param MallConnect $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(MallConnect $entity)
    {
        $form = $this->createForm(MallConnectType::class, $entity, [
            'action' => $this->generateUrl('mallconnect_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing MallConnect entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingContentBundle:MallConnect')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MallConnect entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('mallconnect_edit', ['id' => $id]));
        }

        return $this->render('Content/MallConnect/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a MallConnect entity.
     *
     */
    public function deleteAction(MallConnect $entity)
    {

            $em = $this->em;
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MallConnect entity.');
            }
            $em->remove($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('mallconnect'));
    }

    /**
     * Status a news entity.
     *
     */
    public function statusAction(Request $request, $id)
    {
        $em = $this->em;
        $entity = $em->getRepository('SettingContentBundle:MallConnect')->find($id);

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
        return $this->redirect($this->generateUrl('mallconnect'));
    }

}
