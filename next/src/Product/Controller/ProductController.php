<?php

namespace App\Product\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Product\Entity\ProductGallery;
use Symfony\Component\HttpFoundation\Request;

use App\Product\Entity\Product;
use App\Product\Form\ProductType;

/**
 * Product controller.
 *
 */
class ProductController extends AbstractController
{

    /**
     * Lists all Product entities.
     *
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $search = $this->getRequest()->query->all();
        $user = $this->get('security.context')->getToken()->getUser();
        $entities = $em->getRepository(\App\Product\Entity\Product::class)->getProducts($user->getId(),$search);
        $pagination = $this->paginate($entities);
        $collections = $em->getRepository(\App\Product\Entity\Collection::class)->findBy(['parent'=>NULL,'status'=>1],['name'=>'asc']);

        $grouping = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->getParentCategory($user->getId());
        $categoryArr = [];

        if($grouping){
            foreach($grouping as $row ){
               $categoryArr[] = $row['category_id'];
            }
        }

        $brands = $em->getRepository(\App\Tool\Entity\Branding::class)->getCategoryBrand($categoryArr);
        //\Doctrine\Common\Util\Debug::dump($brands);

        return $this->render('Product/Product/index.html.twig', [
            'pagination' => $pagination,
            'collections' => $collections,
            'categories' => $grouping,
            'brands' => $brands,
        ]);
    }

    public function paginate($entities)
    {

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            30  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Creates a new Product entity.
     *
     */
    public function createAction(Request $request)
    {
        $data = $request->request->all();

        $user = $this->get('security.context')->getToken()->getUser();

        $this->getDoctrine()->getRepository(\App\Product\Entity\Product::class)->insertProduct($data,$user);
        $this->get('session')->getFlashBag()->add(
            'success',"Data has been inserted successfully"
        );
        return $this->redirect($this->generateUrl('product_insert'));
    }



    public function uploadProductAction()
    {
        $entity = new Product();

        $user = $this->get('security.context')->getToken()->getUser();
        $this->get('settong.toolManageRepo')->createDirectory($user,'products');
        $entity ->upload($user);
    }

    public function uploadProductGalleryAction()
    {
        $entity = new ProductGallery();
        $user = $this->get('security.context')->getToken()->getUser();
        $entity ->upload($user);
    }

    /**
     * Lists all Product entities.
     *
     */
    public function insertAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $entities = $em->getRepository(\App\Product\Entity\Product::class)->findBy(['user'=>$user,'status'=>2],['created'=>'desc']);
        // \Doctrine\Common\Util\Debug::dump($entities);

        $productCategories = $em->getRepository(\App\Product\Entity\Category::class)->findBy(['status'=>1,'parent'=>NULL],['name'=>'asc']);
        if($productCategories)
            $grouping = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->findOneBy(['user'=>$user]);
        $array = [];
        if($grouping){

            $groups = $grouping->getCategories();
            foreach($groups as $row ){
                $array[] = $row->getId();
            }
        }
        $productCategories = $em->getRepository(\App\Product\Entity\Category::class)->findBy(['status'=>1,'parent'=>NULL],['name'=>'asc']);
        $categories = $this->getDoctrine()->getRepository(\App\Product\Entity\Category::class)->getSelectdDropdownCategories($productCategories,$array);
        $itemUnits = $this->getDoctrine()->getRepository(\App\Product\Entity\Product::class)->getItemUnit();


        return $this->render('Product/Product/insert.html.twig', [
            'entities' => $entities,
            'itemUnits' =>  $itemUnits,
            'categories' =>  $categories

        ]);
    }

    /**
     * Lists all Product entities.
     *
     */
    public function multiinsertAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $data = $request->request->all();
        if(!empty($data['name'])){
            $this->getDoctrine()->getRepository(\App\Product\Entity\Product::class)->multiInsert($data);
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('product'));
        }else{
            return $this->redirect($this->generateUrl('product_insert'));

        }
    }

    /**
     * Creates a form to create a Product entity.
     *
     * @param Product $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Product $entity)
    {
        $form = $this->createForm(new ProductType(), $entity, [
            'action' => $this->generateUrl('product_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', 'submit', ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new Product entity.
     *
     */
    public function newAction()
    {
        $entity = new Product();
        return $this->render('Product/Product/new.html.twig', [
            'entity' => $entity

        ]);
    }

    /**
     * Finds and displays a Product entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Product\Entity\Product::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Product/Product/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Product entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();


        $entity = $em->getRepository(\App\Product\Entity\Product::class)->findOneBY(['id'=>$id,'user'=>$user]);

        $parentId = $entity->getParentCategory()->getId();

        $productCategories = $em->getRepository(\App\Product\Entity\Category::class)->findBy(['status'=>1,'parent' => $parentId],['name'=>'asc']);

        $grouping = $em->getRepository(\App\Product\Entity\CategoryGrouping::class)->findOneBy(['user'=>$user]);

        $categories = $this->getDoctrine()->getRepository(\App\Product\Entity\Category::class)->getSelectedCategories($productCategories,$entity);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        $collections = $em->getRepository(\App\Product\Entity\Collection::class)->findBy(['parent'=>NULL,'status'=>1],['name'=>'asc']);

        return $this->render('Product/Product/edit.html.twig', [
            'entity'      => $entity,
            'collections'      => $collections,
            'categories'      => $categories,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }
    public function duplicateAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(\App\Product\Entity\Product::class)->find($id);
        $newEntity = $this->getDoctrine()->getRepository(\App\Product\Entity\Product::class)->insertDuplicate($entity);
        $this->getDoctrine()->getRepository(\App\Product\Entity\Product::class)->updateDuplicateProduct($newEntity);
        $this->getDoctrine()->getRepository(\App\Product\Entity\ProductCustomAttribute::class)->insertDuplicateProductAttribute($newEntity,$entity);
        $this->getDoctrine()->getRepository(\App\Product\Entity\ProductGallery::class)->insertDuplicateProductGallery($newEntity,$entity);
        return $this->redirect($this->generateUrl('product'));



    }

    /**
     * Creates a form to edit a Product entity.
     *
     * @param Product $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Product $entity)
    {

        $form = $this->createForm(new ProductType(), $entity, [
            'action' => $this->generateUrl('product_update', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);


        return $form;
    }
    /**
     * Edits an existing Product entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $data = $request->request->all();
        $entity = $em->getRepository(\App\Product\Entity\Product::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $cat = [];
        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $categories     = $data['categories'];
            foreach($categories as $cid ){
                $cat[] = $em->getRepository(\App\Product\Entity\Category::class)->findOneBy(['id'=>$cid]);
            }
            if (!empty($cat)) {
                $entity->setCategories($cat);
            }

            $entity->editUpload($entity->getUser()->getUserName());
            $em->flush();
            $this->getDoctrine()->getRepository(\App\Product\Entity\Product::class)->updateCollection($entity,$data);
            $this->getDoctrine()->getRepository(\App\Product\Entity\ProductCustomAttribute::class)->insertProductAttribute($entity,$data);
            $this->getDoctrine()->getRepository(\App\Product\Entity\ProductGallery::class)->insertProductGallery($entity,$data);

            $this->get('session')->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );

            return $this->redirect($this->generateUrl('product_edit', ['id' => $id]));
        }

        return $this->redirect($this->generateUrl('product_edit', ['id' => $id]));

        /*return $this->render('Product/Product/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));*/
    }



    /**
     * Deletes a Product entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository(\App\Product\Entity\Product::class)->find($id);

            if (!$entity) {

                throw $this->createNotFoundException('Unable to find Product entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('product'));
    }

    /**
     * Creates a form to delete a Product entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('product_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', ['label' => 'Delete'])
            ->getForm()
            ;
    }

    public function discountAction(Request $request )
    {

        $data = $request ->request->all();
        $this->getDoctrine()->getRepository(\App\Product\Entity\Product::class)->insertProductCollection($data);
        exit;


    }

}
