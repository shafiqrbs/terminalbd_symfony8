<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\InventoryConfig;
use App\Inventory\Form\InventoryConfigType;

/**
 * InventoryConfig controller.
 *
 */
class InventoryConfigController extends AbstractController
{

    /**
     * Lists all InventoryConfig entities.
     *
     */
    public function indexAction()
    {

        $em = $this->getDoctrine()->getManager();
        /* @var GlobalOption $globalOption */
        $globalOption = $this->getUser()->getGlobalOption();

        return $this->render('Inventory/InventoryConfig/dashboard.html.twig', [
            'globalOption' => $globalOption,
        ]);
    }

    public function excelDataImportFormAction(InventoryConfig $inventory)
    {
        //echo $inventoryConfig->getId();
        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository(\App\Inventory\Entity\Item::class)->findBy([]);
        $data = $em->getRepository(\App\Inventory\Entity\ProductImport::class)->getMasterItem();
        $variant = $em->getRepository(\App\Inventory\Entity\ProductImport::class)->getColorSizeUnit('unit');
        $color = $em->getRepository(\App\Inventory\Entity\ProductImport::class)->getColor();
        $size = $em->getRepository(\App\Inventory\Entity\ProductImport::class)->getSize();
        $vendor = $em->getRepository(\App\Inventory\Entity\ProductImport::class)->getVendor();

        //var_dump($color);
        //$vendor = $em->getRepository(\App\Inventory\Entity\ProductImport::class)->insertVendor($inventory,$vendor);
        //$variant = $em->getRepository(\App\Inventory\Entity\ProductImport::class)->insertVariant($inventory,'unit',$color);
         $em->getRepository(\App\Inventory\Entity\ProductImport::class)->insertColor($inventory,$color);
         $em->getRepository(\App\Inventory\Entity\ProductImport::class)->insertSize($inventory,$size);
        //$entities = $em->getRepository(\App\Inventory\Entity\ProductImport::class)->masterItemAdd($inventory,$data);

        return $this->render('Inventory/InventoryConfig/dataImport.html.twig', [
            'entities' => $data,
        ]);

    }



    /**
     * Creates a new InventoryConfig entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new InventoryConfig();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data = $request->request->all();

            if($data['brandvendor'] == 'vendor'){
                $entity->setIsVendor(true);
                $entity->setIsBrand(false);
            }elseif($data['brandvendor'] == 'brand'){
                $entity->setIsVendor(false);
                $entity->setIsBrand(true);
            }else{
                $entity->setIsVendor(false);
                $entity->setIsBrand(false);
            }
            if($entity->getRemoveImage() == 1 ){
                $entity->removeUpload();
            }elseif($entity->getRemoveImage() != 1) {
                $entity->upload();
            }
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('inventoryconfig_show', ['id' => $entity->getId()]));
        }

        return $this->render('Inventory/InventoryConfig/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a InventoryConfig entity.
     *
     * @param InventoryConfig $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(InventoryConfig $entity)
    {
        $form = $this->createForm(new InventoryConfigType(), $entity, [
            'action' => $this->generateUrl('inventoryconfig_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);

        $form->add('submit', 'submit', ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new InventoryConfig entity.
     *
     */
    public function newAction()
    {
        $entity = new InventoryConfig();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/InventoryConfig/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a InventoryConfig entity.
     *
     */

    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            50  /*limit per page*/
        );
        return $pagination;
    }

    public function showAction($id)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository(\App\Inventory\Entity\Item::class)->findBy(['inventoryConfig'=> $id],['id'=>'ASC']);
        $pagination = $this->paginate($entities);

        echo "<ul>";
        foreach($pagination as $entity){

            $masterItem         = $entity->getMasterItem()->getSTRPadCode();
            $masterSlug         = $entity->getMasterItem()->getSlug();
            $masterName         = $entity->getMasterItem()->getName();


            $color ='';
            $colorName ='';

            if(!empty($entity->getInventoryConfig()->getIsColor()) and $entity->getInventoryConfig()->getIsColor() == 1 ){
                $color              = '-C'.$entity->getColor()->getSTRPadCode();
                $colorSlug          = $entity->getColor()->getSlug();
                $colorName          = '-'.$entity->getColor()->getName();
            }elseif(!empty($entity->getColor())){
                $colorSlug          =$entity->getColor()->getSlug();
            }else{
                $colorSlug ='';
            }

            $size ='';
            $sizeName = '';

            if(!empty($entity->getInventoryConfig()->getIsSize()) and $entity->getInventoryConfig()->getIsSize() == 1){
                $size               = '-S'.$entity->getSize()->getSTRPadCode();
                $sizeSlug           = $entity->getSize()->getSlug();
                $sizeName           = '-'.$entity->getSize()->getName();
            }elseif(!empty($entity->getSize())){
                $sizeSlug           = $entity->getSize()->getSlug();
            }else{
                $sizeSlug = '';
            }

            $brand ='';
            $brandName = '';

            if(!empty($entity->getInventoryConfig()->getIsBrand()) and $entity->getInventoryConfig()->getIsBrand() == 1){
                $brand               = '-B'.$entity->getBrand()->getSTRPadCode();
                $brandSlug           = $entity->getBrand()->getSlug();
                $brandName           = '-'.$entity->getBrand()->getName();
            }elseif(!empty($entity->getBrand())){
                $brandSlug           = $entity->getBrand()->getSlug();
            }else{
                $brandSlug = '';
            }


            $vendor ='';
            $vendorName ='';

            if(!empty($entity->getInventoryConfig()->getIsVendor()) and $entity->getInventoryConfig()->getIsVendor() == 1 ){
                $vendor             = '-V'.$entity->getVendor()->getSTRPadCode();
                $vendorSlug         =  $entity->getVendor()->getSlug();
                $vendorName         = '-'.$entity->getVendor()->getVendorCode();
            }elseif(!empty($entity->getVendor())){
                $vendorSlug           = $entity->getVendor()->getSlug();
            }else{
                $vendorSlug = '';
            }

            $sku            = $masterItem.$color.$size.$brand.$vendor;
            $name           = $masterName.$colorName.$sizeName.$brandName.$vendorName;
            $skuSlug        = $masterSlug.$colorSlug.$sizeSlug.$brandSlug.$vendorSlug;

            echo '<li>P'.$entity->getId().'==='.$name.'==='.$sku.'</li>';

            $entity->setName($name);
            $entity->setSku($sku);
            $entity->setSlug($skuSlug);
            $em->persist($entity);
            $em->flush();
        }
        echo "<ul>";
        $this->get('session')->getFlashBag()->add(
            'success', "Item has been added successfully"
        );
        exit;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_CONFIG')]

    public function editAction()
    {

        $entity = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/InventoryConfig/new.html.twig', [
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a InventoryConfig entity.
     *
     * @param InventoryConfig $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(InventoryConfig $entity)
    {
        $form = $this->createForm(new InventoryConfigType(), $entity, [
            'action' => $this->generateUrl('inventoryconfig_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_CONFIG')]

    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\InventoryConfig::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InventoryConfig entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $data = $request->request->all();
            if($data['brandvendor'] == 'vendor'){
                $entity->setIsVendor(true);
                $entity->setIsBrand(false);
            }elseif($data['brandvendor'] == 'brand'){
                $entity->setIsVendor(false);
                $entity->setIsBrand(true);
            }else{
                $entity->setIsVendor(false);
                $entity->setIsBrand(false);
            }
            if($entity->getRemoveImage() == 1 ){
                $entity->removeUpload();
            }elseif($entity->getRemoveImage() != 1) {
                $entity->upload();
            }
            $em->flush();
            return $this->redirect($this->generateUrl('inventoryconfig_edit', ['id' => $id]));
        }

        return $this->render('Inventory/InventoryConfig/new.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ]);
    }


}
