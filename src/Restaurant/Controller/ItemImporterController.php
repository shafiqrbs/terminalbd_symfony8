<?php

namespace App\Restaurant\Controller;

use App\Business\Importer\ProductDataReader;

use App\Restaurant\Importer\ProductExcel;


use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Restaurant\Entity\ItemImport;
use App\Restaurant\Form\ItemImporterType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



/**
 * ItemImporter controller.
 *
 */
class ItemImporterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly ProductExcel $productExcel,
        private readonly ProductDataReader $productDataReader,
    ) {
    }


    /**
     * Lists all ItemImporter entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $entities = $em->getRepository(\App\Restaurant\Entity\ItemImport::class)->findBy(array('restaurantConfig'=>$config),array('updated'=>'DESC'));
        return $this->render('Restaurant/ItemImporter/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new ItemImporter entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new ItemImport();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
            $entity->setRestaurantConfig($config);
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_itemimporter'));
        }
        return $this->render('Restaurant/ItemImporter/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a ItemImporter entity.
     *
     * @param ItemImport $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(ItemImport $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(ItemImporterType::class, $entity, array('action' => $this->generateUrl('restaurant_itemimporter_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',
            ), 'option' => $globalOption));
        return $form;
    }

    /**
     * Displays a form to create a new ItemImporter entity.
     *
     */
    public function newAction()
    {
        $entity = new ItemImport();
        $form   = $this->createCreateForm($entity);

        return $this->render('Restaurant/ItemImporter/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    public function excelDataImportAction(ItemImport $ItemImporter)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->em;
        $importer = $this->productExcel;
        $importer->setItemImport($ItemImporter);
        $reader = $this->productDataReader;
        $file =  realpath($ItemImporter->getAbsolutePath());
        $data = $reader->getData($file);
        $importer->import($data);
        $ItemImporter->setProgress('migrated');
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Data has been migration successfully"
        );
        $em->flush();
        return $this->redirect($this->generateUrl('restaurant_itemimporter'));
    }

    /**
     * Displays a form to edit an existing ItemImporter entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Restaurant\Entity\ItemImport::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemImporter entity.');
        }

        $editForm = $this->createEditForm($entity);
        return $this->render('Restaurant/ItemImporter/new.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),

        ));
    }

    /**
    * Creates a form to edit a ItemImporter entity.
    *
    * @param ItemImport $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(ItemImport $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createForm(ItemImporterType::class, $entity, array('action' => $this->generateUrl('restaurant_itemimporter_update', array('id' => $entity->getId())),
            'method' => 'PUT', 'option' => $globalOption));
        return $form;
    }
    /**
     * Edits an existing ItemImporter entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Restaurant\Entity\ItemImport::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ItemImporter entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            return $this->redirect($this->generateUrl('restaurant_itemimporter_edit', array('id' => $id)));
        }

        return $this->render('Restaurant/ItemImporter/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),

        ));
    }
    /**
     * Deletes a ItemImporter entity.
     *
     */
    public function deleteAction(ItemImport $ItemImporter)
    {
        $em = $this->em;
        if ($ItemImporter) {
            $ItemImporter->removeUpload();
            $em->remove($ItemImporter);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('restaurant_itemimporter'));
    }

    public function updatedAction(ItemImport $ItemImporter)
    {



    }
    public function resetedAction(ItemImport $en)
    {

    }

}
