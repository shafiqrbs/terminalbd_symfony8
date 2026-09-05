<?php

namespace App\Content\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Content\Form\MallShopType;
use Symfony\Component\HttpFoundation\Request;

use App\Content\Entity\MallConnect;

/**
 * MallShopController controller.
 *
 */
class MallShopController extends AbstractController
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
        $entities = $em->getRepository('SettingContentBundle:MallConnect')->findBy(['mall'=>$globalOption]);
        return $this->render('Content/MallConnect/mall.html.twig', [
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
                $mall = $this->getUser()->getGlobalOption();
                $entity->setMall($mall);
                $entity->upload();
                $em->persist($entity);
                $em->flush();
                $this->requestStack->getSession()->getFlashBag()->add(
                    'success',"Data has been inserted successfully"
                );
                return $this->redirect($this->generateUrl('mallshop'));
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"This data is already in use"
        );
        return $this->render('Content/MallConnect/edit.html.twig', [
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
        $form = $this->createForm(MallShopType::class, $entity, [
            'action' => $this->generateUrl('mallshop_create'),
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

        return $this->render('Content/MallConnect/edit.html.twig', [
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

        $entity = $em->getRepository('SettinMallShopTypegContentBundle:MallConnect')->find($id);

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

        return $this->render('Content/MallConnect/edit.html.twig', [
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
        $form = $this->createForm(MallShopType::class, $entity, [
            'action' => $this->generateUrl('mallshop_update', ['id' => $entity->getId()]),
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

            if($entity->upload()){
                $entity->removeUpload();
                $entity->upload();
            }
            $em->flush();

            return $this->redirect($this->generateUrl('mallshop_edit', ['id' => $id]));
        }

        return $this->render('Content/MallConnect/edit.html.twig', [
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
            return $this->redirect($this->generateUrl('mallshop'));
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

        $status = $entity->getMallStatus();
        if($status == 1){
            $entity->setMallStatus(0);
        } else{
            $entity->setMallStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('mallshop'));
    }

}
