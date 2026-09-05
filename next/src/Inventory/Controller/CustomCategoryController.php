<?php

namespace App\Inventory\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Inventory\Form\CustomCategoryType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Product\Entity\Category;
use App\Product\Form\CategoryType;


/**
 * Category controller.
 *
 */
class CustomCategoryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {

        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        $pagination->setTemplate('Tool/Widget/pagination.html.twig');
        return $pagination;
    }

    /**
     * Lists all Category entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $inventoryConfig = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Product\Entity\Category::class)->findBy(array('inventoryConfig' => $inventoryConfig ),array( 'name' =>'asc' ));
        $pagination = $this->paginate($entities);
        return $this->render('Inventory/CustomCategory/index.html.twig', array(
            'entities' => $pagination,
        ));

    }


    /**
     * Creates a new Category entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Category();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $inventoryConfig =  $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventoryConfig);
            $entity->setStatus(true);
            $entity->setPermission('private');
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('inventory_category', array('id' => $entity->getId())));
        }

        return $this->render('Inventory/CustomCategory/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Category entity.
     *
     * @param Category $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Category $entity)
    {

        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em = $this->em->getRepository(\App\Product\Entity\Category::class);
        $form = $this->createForm(CustomCategoryType::class, $entity, array('action' => $this->generateUrl('inventory_category_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'inventoryConfig' => $inventory, 'em' => $em));
        return $form;
    }

    /**
     * Displays a form to create a new Category entity.
     *
     */
    public function newAction()
    {
        $entity = new Category();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/CustomCategory/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Category entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Product\Entity\Category::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Category entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Inventory/CustomCategory/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Category entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entity = $em->getRepository(\App\Product\Entity\Category::class)->findOneBy(array('inventoryConfig'=>$config,'id'=>$id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Category entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/CustomCategory/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Category entity.
     *
     * @param Category $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Category $entity)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em = $this->em->getRepository(\App\Product\Entity\Category::class);
        $form = $this->createForm(CustomCategoryType::class, $entity, array('action' => $this->generateUrl('inventory_category_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'novalidate' => 'novalidate',
                'class' => 'form-horizontal',
            ), 'inventoryConfig' => $inventory, 'em' => $em));
        return $form;
    }
    /**
     * Edits an existing Category entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Product\Entity\Category::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Category entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {

            $entity->upload();
            $em->flush();
            return $this->redirect($this->generateUrl('inventory_category'));
        }

        return $this->render('Inventory/CustomCategory/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Category entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getInventoryConfig()->getId();
        $entity = $em->getRepository(\App\Product\Entity\Category::class)->findOneBy(array('inventoryConfig'=>$config,'id'=>$id));
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find item entity.');
        }

        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }

        return $this->redirect($this->generateUrl('inventory_category'));
    }

    /**
     * Creates a form to delete a Category entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('inventory_category_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm();
    }
}
