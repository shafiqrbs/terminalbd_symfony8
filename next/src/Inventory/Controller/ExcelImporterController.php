<?php

namespace App\Inventory\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Inventory\Entity\InventoryConfig;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\ExcelImporter;
use App\Inventory\Form\ExcelImporterType;

/**
 * ExcelImporter controller.
 *
 */
class ExcelImporterController extends AbstractController
{

    /**
     * Lists all ExcelImporter entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\ExcelImporter::class)->findBy(['inventoryConfig'=>$inventory],['updated'=>'DESC']);
        return $this->render('Inventory/ExcelImporter/index.html.twig', [
            'entities' => $entities,
        ]);
    }
    /**
     * Creates a new ExcelImporter entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ExcelImporter();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventory);
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('inventory_excelimproter'));
        }

        return $this->render('Inventory/ExcelImporter/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a ExcelImporter entity.
     *
     * @param ExcelImporter $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ExcelImporter $entity)
    {
        $form = $this->createForm(new ExcelImporterType(), $entity, [
            'action' => $this->generateUrl('inventory_excelimproter_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new ExcelImporter entity.
     *
     */
    public function newAction()
    {
        $entity = new ExcelImporter();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/ExcelImporter/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    public function excelDataImportAction(ExcelImporter $excelImporter)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $inventory = $excelImporter->getInventoryConfig();

        if($excelImporter->getMode() == 'purchase'){
            $importer = $this->get('appstore_inventory_importer_excel');
            $importer->setInventoryConfig($inventory->getId());
            $reader = $this->get('appstore_inventory.importer.excel_data_reader');
        }else{
            $importer = $this->get('appstore_inventory_product_import');
            $importer->setExcelImport($excelImporter);
            $reader = $this->get('appstore_inventory.importer.product_excel_data_reader');
        }
        $file =  realpath($excelImporter->getAbsolutePath());
        $data = $reader->getData($file);
        if($importer->isValid($data)) {
            $importer->import($data);
            $excelImporter->setProgress('migrated');
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been migration successfully"
            );
            $em->flush();
            return $this->redirect($this->generateUrl('inventory_purchasesimple'));
        }else{
            $this->get('session')->getFlashBag()->add(
                'error',"Excel File memo no null or exist in your system"
            );
            $excelImporter->setProgress('invalid');
            $em->flush();
        }
        return $this->redirect($this->generateUrl('inventory_excelimproter'));
    }

    /**
     * Displays a form to edit an existing ExcelImporter entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\ExcelImporter::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ExcelImporter entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/ExcelImporter/new.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),

        ]);
    }

    /**
     * Creates a form to edit a ExcelImporter entity.
     *
     * @param ExcelImporter $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ExcelImporter $entity)
    {
        $form = $this->createForm(new ExcelImporterType(), $entity, [
            'action' => $this->generateUrl('inventory_excelimproter_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);
        return $form;
    }
    /**
     * Edits an existing ExcelImporter entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\ExcelImporter::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ExcelImporter entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            return $this->redirect($this->generateUrl('inventory_excelimproter_edit', ['id' => $id]));
        }

        return $this->render('Inventory/ExcelImporter/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),

        ]);
    }
    /**
     * Deletes a ExcelImporter entity.
     *
     */
    public function deleteAction(ExcelImporter $excelImporter)
    {
        $em = $this->getDoctrine()->getManager();
        if ($excelImporter) {
            $excelImporter->removeUpload();
            $em->remove($excelImporter);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('inventory_excelimproter'));
    }

    public function updatedAction(ExcelImporter $excelImporter)
    {

        set_time_limit(0);
        $em = $this->getDoctrine()->getManager();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();

        /*  This function is written for sum total item base.  */
        $em->getRepository(\App\Inventory\Entity\Item::class)->getSumPurchaseItem($inventory,$excelImporter);

        /*  This function is written for sum total item base.  */
        $varified = $em->getRepository(\App\Inventory\Entity\Purchase::class)->getSumPurchase( $this->getUser(),$inventory);
        if($varified == 'imported'){
            $entities = $em->getRepository(\App\Inventory\Entity\Purchase::class)->findBy(['inventoryConfig' => $inventory,'process' => 'imported'],['created'=>'asc']);
            foreach($entities as $entity ){
                if($entity->getTotalAmount() > 0 ){
                    $accountPurchase = $em->getRepository(\App\Accounting\Entity\AccountPurchase::class)->insertAccountPurchase($entity,$inventory);
                    $em->getRepository(\App\Accounting\Entity\Transaction::class)->purchaseTransaction($entity,$accountPurchase,'Purchase');
                    $em->getRepository(\App\Inventory\Entity\Purchase::class)->updateProcess($entity,'approved');
                }
            }
            $excelImporter->setProgress('updated');
        }

        $em->persist($excelImporter);
        $em->flush();
        $this->get('session')->getFlashBag()->add(
            'success',"Data has been updated successfully"
        );
        return $this->redirect($this->generateUrl('inventory_excelimproter'));

    }
    public function resetedAction(InventoryConfig $inventoryConfig)
    {
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',"Data has been updated successfully"
        );

        return $this->redirect($this->generateUrl('inventory_excelimproter'));

    }

    public function restoredAction(InventoryConfig $inventoryConfig)
    {
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',"Data has been updated successfully"
        );

        return $this->redirect($this->generateUrl('inventory_excelimproter'));

    }


}
