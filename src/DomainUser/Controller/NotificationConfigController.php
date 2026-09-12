<?php

namespace App\DomainUser\Controller;
use App\Tool\Service\ToolManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use App\DomainUser\Entity\NotificationConfig;
use App\DomainUser\Form\NotificationConfigType;

/**
 * NotificationConfig controller.
 *
 */
class NotificationConfigController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly ToolManager $toolManager,
    ) {
    }



    /**
     * Lists all NotificationConfig entities.
     */

    public function indexAction()
    {

        $global = $this->getUser()->getGlobalOption();
        $entity = $this->em->getRepository(\App\DomainUser\Entity\NotificationConfig::class)->findOneBy(['globalOption'=>$global]);
        if(empty($entity)){
            $entity = new NotificationConfig();
            $em = $this->em;
            $entity->setGlobalOption($global);
            $em->persist($entity);
            $em->flush();
        }
        $form   = $this->createEditForm($entity);
        return $this->render('DomainUser/NotificationConfig/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to edit a NotificationConfig entity.
     *
     * @param NotificationConfig $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(NotificationConfig $entity)
    {
        $form = $this->createForm(NotificationConfigType::class, $entity, [
            'action' => $this->generateUrl('domain_notificationconfig_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
         return $form;
    }
    /**
     * Edits an existing NotificationConfig entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\DomainUser\Entity\NotificationConfig::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find NotificationConfig entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $mobile = $this->toolManager->specialExpClean($entity->getMobile());
            $entity->setMobile($mobile);
            $notification = $this->toolManager->specialExpClean($entity->getPaymentNotification());
            $entity->setPaymentNotification($notification);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('domain_notificationconfig'));
        }

        return $this->render('DomainUser/NotificationConfig/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }



}
