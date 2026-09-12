<?php

namespace App\Appearance\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Appearance\Entity\MenuCustom;
use App\Appearance\Form\MenuCustomType;

/**
 * MenuCustom controller.
 *
 */
class MenuCustomController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all MenuCustom entities.
     *
     */
    public function indexAction()
    {

        $em = $this->em;
        $entities = $em->getRepository(\App\Appearance\Entity\MenuCustom::class)->findAll();

        /*$globalOptions = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->findBy(array('status'=>1));
        foreach ($globalOptions as $globalOption)
        {

            foreach( $entities as $custom){

                $menu = new Menu();
                $menu->setGlobalOption($globalOption);
                $menu->setMenuCustom($custom);
                $menu->setMenu($custom->getMenu());
                $menu->setMenuSlug($globalOption->getSlug().'-'.$custom->getSlug());
                $menu->setSlug($custom->getSlug());
                $menu->setStatus(0);
                $em->persist($menu);
            }

        }*/

        $em->flush();

        return $this->render('Appearance/MenuCustom/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new MenuCustom entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MenuCustom();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('menucustom'));
        }

        return $this->render('Appearance/MenuCustom/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a MenuCustom entity.
     *
     * @param MenuCustom $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MenuCustom $entity)
    {
        $form = $this->createForm(MenuCustomType::class, $entity, [
            'action' => $this->generateUrl('menucustom_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new MenuCustom entity.
     *
     */
    public function newAction()
    {
        $entity = new MenuCustom();
        $form   = $this->createCreateForm($entity);

        return $this->render('Appearance/MenuCustom/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a MenuCustom entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Appearance\Entity\MenuCustom::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MenuCustom entity.');
        }
        return $this->render('Appearance/MenuCustom/show.html.twig', [
            'entity'      => $entity,

        ]);
    }

    /**
     * Displays a form to edit an existing MenuCustom entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Appearance\Entity\MenuCustom::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MenuCustom entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Appearance/MenuCustom/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a MenuCustom entity.
     *
     * @param MenuCustom $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(MenuCustom $entity)
    {
        $form = $this->createForm(MenuCustomType::class, $entity, [
            'action' => $this->generateUrl('menucustom_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing MenuCustom entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Appearance\Entity\MenuCustom::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MenuCustom entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('menucustom'));
        }

        return $this->render('Appearance/MenuCustom/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a MenuCustom entity.
     *
     */
    public function deleteAction(MenuCustom $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MenuCustom entity.');
        }

        $em->remove($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('menucustom'));
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Appearance\Entity\MenuCustom::class)->find($id);

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
        return $this->redirect($this->generateUrl('menucustom'));
    }


}
