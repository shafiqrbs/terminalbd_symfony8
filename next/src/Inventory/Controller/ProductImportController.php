<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;

use App\Inventory\Entity\SalesImport;
use App\Inventory\Form\SalesImportType;

/**
 * SalesImport controller.
 *
 */
class ProductImportController extends AbstractController
{

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\SalesImport::class)->findBy(['inventoryConfig'=>$inventory],['updated'=>'DESC']);
        return $this->render('Inventory/SalesImport/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function createAction(Request $request)
    {
        $entity = new SalesImport();
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
            return $this->redirect($this->generateUrl('inventory_salesimport'));
        }

        return $this->render('Inventory/SalesImport/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a SalesImport entity.
     *
     * @param SalesImport $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(SalesImport $entity)
    {
        $form = $this->createForm(new SalesImportType(), $entity, [
            'action' => $this->generateUrl('inventory_salesimport_create'),
            'method' => 'POST',
        ]);
        return $form;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function newAction()
    {
        $entity = new SalesImport();
        $form   = $this->createCreateForm($entity);

        return $this->render('Inventory/SalesImport/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function excelDataImportAction(SalesImport $SalesImport)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        $importer = $this->get('appstore_inventory_sales_import');
        $importer->setSalesImport($SalesImport);
        $reader = $this->get('appstore_inventory.importer.sales_excel_data_reader');
        $file =  realpath($SalesImport->getAbsolutePath());
        $importer->import($reader->getData($file));

        $SalesImport->setProgress('migrated');
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'success',"Data has been migration successfully"
        );
        return $this->redirect($this->generateUrl('inventory_salesimport'));
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\Sales::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalesImport entity.');
        }
        $editForm = $this->createEditForm($entity);
        return $this->render('Inventory/SalesImport/new.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),

        ]);
    }

    /**
     * Creates a form to edit a SalesImport entity.
     *
     * @param SalesImport $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(SalesImport $entity)
    {
        $form = $this->createForm(new SalesImportType(), $entity, [
            'action' => $this->generateUrl('inventory_salesimport_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);
        return $form;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Inventory\Entity\SalesImport::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SalesImport entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('inventory_salesimport_edit', ['id' => $id]));
        }

        return $this->render('Inventory/SalesImport/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),

        ]);
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function deleteAction(SalesImport $salesImport)
    {
        $em = $this->getDoctrine()->getManager();
        if ($salesImport) {
            $em->remove($salesImport);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('inventory_salesimport'));
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function updatedAction(SalesImport $salesImport)
    {

        $em = $this->getDoctrine()->getManager();
        $this->getDoctrine()->getRepository(\App\Inventory\Entity\Sales::class)->updateSalesTotalPrice($salesImport->getSales(),'import');
        $salesImport->setProgress('updated');
        $em->persist($salesImport);
        $em->flush();
        $this->get('session')->getFlashBag()->add(
            'success',"Data has been updated successfully"
        );

        return $this->render('Inventory/SalesImport/show.html.twig', [
            'entity'      => $salesImport->getSales(),

        ]);

    }

}
