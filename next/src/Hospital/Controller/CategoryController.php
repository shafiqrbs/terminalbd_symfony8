<?php

namespace App\Hospital\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Hospital\Entity\HmsCategory;
use App\Hospital\Form\CategoryType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * Category controller.
 *
 */
class CategoryController extends AbstractController
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
            50  /*limit per page*/
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
        $data = $_REQUEST;
        $entities = $em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findWithSearch($data);
        $pagination = $this->paginate($entities);
        $categories = $em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findBy(array('level'=> 1),array('name' =>'asc' ));
        return $this->render('Hospital/Category/index.html.twig', array(
            'entities' => $pagination,
            'categories' => $categories,
            'searchForm' => $data,
        ));

    }

    /**
     * Creates a new Category entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new HmsCategory();
        $globalOption = $this->getUser()->getGlobalOption();
        $form = $this->createCreateForm($entity,$globalOption);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('hms_category_new', array('id' => $entity->getId())));
        }

        return $this->render('Hospital/Category/new.html.twig', array(
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
    private function createCreateForm(HmsCategory $entity, $globalOption)
    {

        $em = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class);
        $form = $this->createForm(CategoryType::class, $entity, array('action' => $this->generateUrl('hms_category_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'emCategory' => $em, 'globalOption' => $globalOption));
        return $form;
    }

    /**
     * Displays a form to create a new Category entity.
     *
     */
    public function newAction()
    {
        $entity = new HmsCategory();
        $globalOption = $this->getUser()->getGlobalOption();
        $form   = $this->createCreateForm($entity,$globalOption);

        return $this->render('Hospital/Category/new.html.twig', array(
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

    }

    /**
     * Displays a form to edit an existing Category entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Hospital\Entity\HmsCategory::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Category entity.');
        }
        $globalOption = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity,$globalOption);

        return $this->render('Hospital/Category/new.html.twig', array(
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
    private function createEditForm(HmsCategory $entity,$globalOption)
    {
        $em = $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class);
        $form = $this->createForm(CategoryType::class, $entity, array('action' => $this->generateUrl('hms_category_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'emCategory' => $em, 'globalOption' => $globalOption));


        return $form;
    }
    /**
     * Edits an existing Category entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Hospital\Entity\HmsCategory::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Category entity.');
        }

        $globalOption = $this->getUser()->getGlobalOption();
        $editForm = $this->createEditForm($entity,$globalOption);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->setSlug($entity->getName());
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('hms_category'));
        }

        return $this->render('Hospital/Category/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a Category entity.
     *
     */
    public function deleteAction(HmsCategory $entity)
    {
        $em = $this->em;
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
        }catch (\Exception $e) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('hms_category'));
    }



    /**
     * Lists all Category entities.
     *
     */
    public function sortingAction()
    {
        $em = $this->em;
        $entities = $em->getRepository(\App\Hospital\Entity\HmsCategory::class)->findBy(array('level'=> 1 ),array('sorting'=>'asc'));
        $pagination = $this->paginate($entities);
        return $this->render('Hospital/Category/sorting.html.twig', array(
            'entities' => $pagination,
        ));

    }

    public function sortedAction(Request $request){

        $data = $request->request->get('menuItem');
        $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class)->setFeatureOrdering($data);
        exit;

    }

    public function featureAction()
    {
        $data = $_REQUEST;
        $this->em->getRepository(\App\Hospital\Entity\HmsCategory::class)->setCategoryFeature($data);
        return $this->redirect($this->generateUrl('hms_category_sorting'));

    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(HmsCategory $entity)
    {


        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find District entity.');
        }

        $status = $entity->getStatus();
        if($status == 1){
            $entity->setStatus(0);
        } else{
            $entity->setStatus(1);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('hms_category'));
    }
}
