<?php

namespace App\Inventory\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\ItemTypeGrouping;
use App\Inventory\Form\ItemTypeGroupingType;

/**
 * ItemTypeGrouping controller.
 *
 */
class ItemTypeGroupingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            20  /*limit per page*/
        );
        return $pagination;
    }

    /**
     * Lists all ItemTypeGrouping entities.
     *
     */
    public function indexAction()
    {

        $em = $this->em;
        $entities = $em->getRepository(\App\Inventory\Entity\ItemTypeGrouping::class)->findAll();
        $entities = $this->paginate($entities);
        return $this->render('Inventory/ItemTypeGrouping/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ItemTypeGrouping entity.
     *
     */
    public function createAction(Request $request)
    {

        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        if($globalOption){
            $entity = $globalOption->getInventoryConfig()->getItemTypeGrouping();
        }else{
            $entity = new ItemTypeGrouping();
        }

        $data = $request->request->get('categories');
        $array = [];
        foreach($data as $row){
            $array[] = $em->getRepository(\App\Product\Entity\Category::class)->find($row);
        }

        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entity->setInventoryConfig($this->getUser()->getGlobalOption()->getInventoryConfig());
            $entity->setCategories($array);
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('itemtypegrouping_edit',['id'=>$entity->getInventoryConfig()->getId()]));
        }


    }

    /**
     * Creates a form to create a ItemTypeGrouping entity.
     *
     * @param ItemTypeGrouping $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ItemTypeGrouping $entity)
    {
        $form = $this->createForm(ItemTypeGroupingType::class, $entity, [
            'action' => $this->generateUrl('itemtypegrouping_create'),
            'method' => 'POST',
        ]);
        return $form;
    }



    /**
     * Displays a form to edit an existing ItemTypeGrouping entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $entity = new ItemTypeGrouping();
        $inventoryConfig = $em->getRepository(\App\Inventory\Entity\InventoryConfig::class)->find($id);
        $array = [];
        $grouping =  $inventoryConfig->getItemTypeGrouping();
        if($grouping){
            $groups = $grouping->getCategories();
            foreach($groups as $row ){
                $array[] = $row->getId();
            }
        }else{
            $entity->setInventoryConfig($inventoryConfig);
            $em->persist($entity);
            $em->flush();
        }

        $categories = $em->getRepository(\App\Product\Entity\Category::class)->findBy(['status' => 1,'inventoryConfig'=>NULL,'parent'=>NULL],['name'=>'asc']);
        if($categories)

        $entities = $this->em->getRepository(\App\Product\Entity\Category::class)->getGroupCategories($categories,$array);
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/ItemTypeGrouping/edit.html.twig', [
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);

    }

    /**
     * Creates a form to edit a ItemTypeGrouping entity.
     *
     * @param ItemTypeGrouping $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ItemTypeGrouping $entity)
    {
        $form = $this->createForm(ItemTypeGroupingType::class, $entity, [
            'action' => $this->generateUrl('itemtypegrouping_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }
    /**
     * Edits an existing ItemTypeGrouping entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemTypeGrouping::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemTypeGrouping entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('itemtypegrouping_edit', ['id' => $id]));
        }

        return $this->render('Inventory/ItemTypeGrouping/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    /**
     * Deletes a ItemTypeGrouping entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Inventory\Entity\ItemTypeGrouping::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ItemTypeGrouping entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('itemtypegrouping'));
    }

    /**
     * Creates a form to delete a ItemTypeGrouping entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('itemtypegrouping_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm()
        ;
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $item = $this->em->getRepository(\App\Inventory\Entity\ItemTypeGrouping::class)->searchAutoComplete($item,$inventory);
        }
        return new JsonResponse($item);
    }

    public function searchVendorNameAction($category)
    {
        return new JsonResponse([
            'id'    =>  $category,
            'text'  =>  $category
        ]);
    }
}
