<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Inventory\Entity\ItemSizeGroup;
use App\Inventory\Form\ItemSizeGroupingType;
use App\Inventory\Form\ItemSizeGroupType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use App\Inventory\Entity\ItemSize;
use App\Inventory\Form\ItemSizeType;

/**
 * ItemSize controller.
 *
 */
class ItemSizeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all ItemSize entities.
     */
    #[IsGranted('ROLE_DOMAIN_INVENTORY_MANAGER')]
    public function indexAction()
    {
        $em = $this->em;
        $entities = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->findBy(array('isValid'=>1),array('code'=>'asc'));
        return $this->render('Inventory/ItemSize/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new ItemSize entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ItemSize();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventory);
            $entity->setStatus(false);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('itemsize'));
        }

        return $this->render('Inventory/ItemSize/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a ItemSize entity.
     *
     * @param ItemSize $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ItemSize $entity)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em = $this->em->getRepository(\App\Product\Entity\Category::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into ItemSizeType::configureOptions() and pass them as form options: $em,$inventory), $entity, array(
        $form = $this->createForm(new ItemSizeType($em,$inventory), $entity, array(
            'action' => $this->generateUrl('itemsize_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }

    /**
     * Displays a form to create a new ItemSize entity.
     */
    #[IsGranted('ROLE_DOMAIN_INVENTORY_MANAGER')]

    public function newAction()
    {
        $entity = new ItemSize();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/ItemSize/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a ItemSize entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemSize entity.');
        }

        return $this->render('Inventory/ItemSize/show.html.twig', array(
            'entity'      => $entity,
        ));
    }

    /**
     * Displays a form to edit an existing ItemSize entity.
     */
    #[IsGranted('ROLE_ADMIN')]
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemSize entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/ItemSize/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a ItemSize entity.
    *
    * @param ItemSize $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(ItemSize $entity)
    {
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $em = $this->em->getRepository(\App\Product\Entity\Category::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into ItemSizeType::configureOptions() and pass them as form options: $em,$inventory), $entity, array(
        $form = $this->createForm(new ItemSizeType($em,$inventory), $entity, array(
            'action' => $this->generateUrl('itemsize_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
         return $form;
    }
    /**
     * Edits an existing ItemSize entity.
     */
    #[IsGranted('ROLE_ADMIN_OPERATION_USER')]
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemSize entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('itemsize_edit', array('id' => $id)));
        }

        return $this->render('Inventory/ItemSize/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a ItemSize entity.
     */
    #[IsGranted('ROLE_ADMIN_OPERATION_USER')]
    public function deleteAction(ItemSize $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
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
        return $this->redirect($this->generateUrl('itemsize'));
    }


    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $item = $this->em->getRepository(\App\Inventory\Entity\ItemSize::class)->searchAutoComplete($item);
        }
        return new JsonResponse($item);
    }

    public function searchItemSizeNameAction($size)
    {
        return new JsonResponse(array(
            'id'=>$size,
            'text'=>$size
        ));
    }

    public function sizeGroupAction(){


        $em = $this->em;
        $entity = new ItemSizeGroup();
        $inventoryConfig = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $array = array();
        $grouping =  $inventoryConfig->getSizeGroup();
        if($grouping){
            $groups = $grouping->getSizes();
            foreach($groups as $row ){
                $array[] = $row->getId();
            }
        }else{
            $entity->setInventoryConfig($inventoryConfig);
            $em->persist($entity);
            $em->flush();
        }

        $sizes = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->findBy(array('isValid'=>1),array('code'=>'asc'));
        if($sizes)
        $entities = $this->em->getRepository(\App\Inventory\Entity\ItemSize::class)->getGroupSizes($sizes,$array);
        $form   = $this->createSizeGroupForm($entity);

        return $this->render('Inventory/ItemSize/group.html.twig', array(
            'entities' => $entities,
            'form'   => $form->createView(),
        ));

    }

    private function createSizeGroupForm(ItemSizeGroup $entity)
    {

        $form = $this->createForm(new ItemSizeGroupType(), $entity, array(
            'action' => $this->generateUrl('itemsize_group_create'),
            'method' => 'POST',
        ));
        return $form;
    }

    public function sizeGroupCreateAction(Request $request)
    {

        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        if($globalOption){
            $entity = $globalOption->getInventoryConfig()->getSizeGroup();
        }else{
            $entity = new ItemSizeGroup();
        }

        $data = $request->request->get('categories');
        $array = array();
        foreach($data as $row){
            $array[] = $em->getRepository(\App\Inventory\Entity\ItemSize::class)->find($row);
        }

        $form = $this->createSizeGroupForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $entity->setInventoryConfig($this->getUser()->getGlobalOption()->getInventoryConfig());
            $entity->setSizes($array);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('itemsize_group'));
        }


    }
}
