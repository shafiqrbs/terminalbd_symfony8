<?php

namespace App\Appearance\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Appearance\Entity\MegaMenu;
use App\Appearance\Form\MegaMenuType;

/**
 * MegaMenu controller.
 *
 */
class MegaMenuController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all MegaMenu entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $entities = $em->getRepository('SettingAppearanceBundle:MegaMenu')->findBy(array(),array('sorting' => 'asc'));
        return $this->render('Appearance/MegaMenu/index.html.twig', array(
            'entities' => $entities,
        ));
    }


    /**
     * Creates a new MegaMenu entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new MegaMenu();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );

            return $this->redirect($this->generateUrl('megamenu_show', array('id' => $entity->getId())));
        }

        return $this->render('Appearance/MegaMenu/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a MegaMenu entity.
     *
     * @param MegaMenu $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MegaMenu $entity)
    {

        $em = $this->em->getRepository(\App\Product\Entity\Category::class);
        $cr = $this->em->getRepository(\App\Product\Entity\Collection::class);
        $sr = $this->em->getRepository(\App\Tool\Entity\Syndicate::class);
        $go = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class);

        $form = $this->createForm(new MegaMenuType($em, $cr , $sr, $go), $entity, array(
            'action' => $this->generateUrl('megamenu_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Displays a form to create a new MegaMenu entity.
     *
     */
    public function ecommerceMenuAction()
    {
        $entity = new MegaMenu();
        $form   = $this->createCreateForm($entity);
        return $this->render('Appearance/MegaMenu/ecommerce.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    /**
     * Displays a form to create a new MegaMenu entity.
     *
     */
    public function newAction()
    {
        $entity = new MegaMenu();
        $form   = $this->createCreateForm($entity);
        return $this->render('Appearance/MegaMenu/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a MegaMenu entity.
     *
     */

    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingAppearanceBundle:MegaMenu')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MegaMenu entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Appearance/MegaMenu/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing MegaMenu entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository('SettingAppearanceBundle:MegaMenu')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MegaMenu entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Appearance/MegaMenu/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a MegaMenu entity.
     *
     * @param MegaMenu $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(MegaMenu $entity)
    {
        $em = $this->em->getRepository(\App\Product\Entity\Category::class);
        $cr = $this->em->getRepository(\App\Product\Entity\Collection::class);
        $sr = $this->em->getRepository(\App\Tool\Entity\Syndicate::class);
        $form = $this->createForm(new MegaMenuType($em, $cr,$sr), $entity, array(
            'action' => $this->generateUrl('megamenu_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));


        return $form;
    }
    /**
     * Edits an existing MegaMenu entity.
     *
     */
    public function updateAction(Request $request, MegaMenu $entity)
    {
        $em = $this->em;



        $deleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );


            return $this->redirect($this->generateUrl('megamenu_edit', array('id' => $entity->getId())));
        }

        return $this->render('Appearance/MegaMenu/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a MegaMenu entity.
     *
     */
    public function deleteAction(MegaMenu $megamenu )
    {

            $em = $this->em;
            if (!$megamenu) {
                throw $this->createNotFoundException('Unable to find MegaMenu entity.');
            }

            $em->remove($megamenu);
            $em->flush();
            return $this->redirect($this->generateUrl('megamenu'));
    }

    /**
     * Creates a form to delete a Admission entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('megamenu_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
            ;
    }




    public function sortedAction(Request $request){

        $data = $request->request->get('menuItem');
        $this->em->getRepository('SettingAppearanceBundle:MegaMenu')->setOrdering($data);
        exit;

    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, Page $entity)
    {
        $form = $this->createDeleteForm($entity->getId());
        $form->handleRequest($request);

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
        return $this->redirect($this->generateUrl('megamenu'));
    }


}
