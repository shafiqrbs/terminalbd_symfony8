<?php

namespace App\Product\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Product\Entity\CategoryGrouping;
use App\Product\Form\CategoryGroupingType;

/**
 * CategoryGrouping controller.
 *
 */
class CategoryGroupingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all CategoryGrouping entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $user = $this->getUser();
        $grouping = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->findOneBy(['user'=>$user]);
        $array = [];
        if($grouping){

            $groups = $grouping->getCategories();
            foreach($groups as $row ){
                $array[] = $row->getId();
            }
        }

        $categories = $em->getRepository(\App\Product\Entity\Category::class)->findBy(['status'=>1,'parent'=>NULL],['name'=>'asc']);
        if($categories)

            $entities = $this->em->getRepository(\App\Product\Entity\Category::class)->getGroupCategories($categories,$array);
            $entity = new CategoryGrouping();
            $form   = $this->createCreateForm($entity);

        return $this->render('Product/CategoryGrouping/index.html.twig', [
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);

    }

    /**
     * Creates a new CategoryGrouping entity.
     *
     */

    public function createAction(Request $request)
    {
        $em = $this->em;
        $user = $this->getUser();

        $entity = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->findOneBy(['user'=>$user]);
        if($entity){
            $entity = $entity;
        }else{
            $entity = new CategoryGrouping();
        }

        $data = $request->request->get('categories');
        $array = [];
        foreach($data as $row){
            $array[] = $em->getRepository(\App\Product\Entity\Category::class)->find($row);
        }

        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entity->setUser($user);
            $entity->setCategories($array);
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('categorygrouping'));
        }

        return $this->render('Product/CategoryGrouping/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a CategoryGrouping entity.
     *
     * @param CategoryGrouping $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(CategoryGrouping $entity)
    {

        $form = $this->createForm(CategoryGroupingType::class, $entity, [
            'action' => $this->generateUrl('categorygrouping_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);

        return $form;


    }

    /**
     * Displays a form to create a new CategoryGrouping entity.
     *
     */
    public function newAction()
    {
        $entity = new CategoryGrouping();
        $form   = $this->createCreateForm($entity);

        return $this->render('Product/CategoryGrouping/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a CategoryGrouping entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CategoryGrouping entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Product/CategoryGrouping/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing CategoryGrouping entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CategoryGrouping entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Product/CategoryGrouping/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a CategoryGrouping entity.
     *
     * @param CategoryGrouping $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(CategoryGrouping $entity)
    {
        $form = $this->createForm(CategoryGroupingType::class, $entity, [
            'action' => $this->generateUrl('categorygrouping_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing CategoryGrouping entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CategoryGrouping entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('categorygrouping_edit', ['id' => $id]));
        }

        return $this->render('Product/CategoryGrouping/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a CategoryGrouping entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find CategoryGrouping entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('categorygrouping'));
    }

    /**
     * Creates a form to delete a CategoryGrouping entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('categorygrouping_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }
}
