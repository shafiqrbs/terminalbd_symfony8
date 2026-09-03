<?php

namespace App\Content\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Content\Entity\ContactPage;
use App\Content\Form\ContactPageType;

/**
 * ContactPage controller.
 *
 */
class ContactPageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all ContactPage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Content\Entity\ContactPage::class)->findOneBy(array('globalOption'=>$globalOption));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ContactPage entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Content/ContactPage/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }

    /**
     * Displays a form to edit an existing ContactPage entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\ContactPage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ContactPage entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Content/ContactPage/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing ContactPage entity.
     *
     */
    public function modifyAction()
    {
        $em = $this->em;

        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Content\Entity\ContactPage::class)->findOneBy(array('globalOption'=>$globalOption));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ContactPage entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Content/ContactPage/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }

    /**
    * Creates a form to edit a ContactPage entity.
    *
    * @param ContactPage $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(ContactPage $entity)
    {

        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into ContactPageType::configureOptions() and pass them as form options: $location), $entity, array(
        $form = $this->createForm(new ContactPageType($location), $entity, array(
            'action' => $this->generateUrl('contactpage_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;

    }
    /**
     * Edits an existing ContactPage entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Content\Entity\ContactPage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ContactPage entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        }

        return $this->render('Content/ContactPage/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView()

        ));
    }
}
