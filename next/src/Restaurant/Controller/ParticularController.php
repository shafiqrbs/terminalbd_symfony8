<?php

namespace App\Restaurant\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Exception;
use Symfony\Component\Form\Form;
use App\Restaurant\Entity\Particular;
use App\Restaurant\Form\ParticularType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * ParticularController controller.
 *
 */
class ParticularController extends AbstractController
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
        return $pagination;
    }


    /**
     * Lists all Particular entities.
     *
     */
    public function indexAction()
    {
        $entity = new Particular();
        $data = $_REQUEST;
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig()->getId();
        $pagination = $em->getRepository(\App\Restaurant\Entity\Particular::class)->findWithSearch($config,['token','value-added']);
        //$pagination = $this->paginate($pagination);
        $editForm = $this->createCreateForm($entity);
        return $this->render('Restaurant/Particular/index.html.twig', [
            'pagination' => $pagination,
            'searchForm' => $data,
            'form'   => $editForm->createView(),
        ]);

    }

    /**
     * Creates a new Particular entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Particular();
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $pagination = $em->getRepository(\App\Restaurant\Entity\Particular::class)->findWithSearch($config,['token','category']);
        //$pagination = $this->paginate($pagination);
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $entity->setRestaurantConfig($config);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_particular'));
        }

        return $this->render('Restaurant/Particular/index.html.twig', [
            'entity' => $entity,
            'pagination' => $pagination,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Particular $entity)
    {
        $form = $this->createForm(ParticularType::class, $entity, [
            'action' => $this->generateUrl('restaurant_particular_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }



    /**
     * Displays a form to edit an existing Particular entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getHospitalConfig();
        $pagination = $em->getRepository(\App\Restaurant\Entity\Particular::class)->findWithSearch($config,['token','category']);
        //$pagination = $this->paginate($pagination);
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);

        return $this->render('Restaurant/Particular/index.html.twig', [
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Particular entity.
     *
     * @param Particular $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Particular $entity)
    {

        $form = $this->createForm(ParticularType::class, $entity, [
            'action' => $this->generateUrl('restaurant_particular_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }
    /**
     * Edits an existing Particular entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;
        $config = $this->getUser()->getGlobalOption()->getRestaurantConfig();
        $pagination = $em->getRepository(\App\Restaurant\Entity\Particular::class)->findWithSearch($config,['token','category']);
        //$pagination = $this->paginate($pagination);
        $entity = $em->getRepository(\App\Restaurant\Entity\Particular::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('restaurant_particular'));
        }

        return $this->render('Restaurant/Particular/index.html.twig', [
            'entity'      => $entity,
            'pagination'      => $pagination,
            'form'   => $editForm->createView(),
        ]);
    }
    /**
     * Deletes a Particular entity.
     *
     */
    public function deleteAction(Particular $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Particular entity.');
        }
        try {

            $em->remove($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'error',"Data has been deleted successfully"
            );

        } catch (ForeignKeyConstraintViolationException) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Data has been relation another Table"
            );
        }catch (Exception) {
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice', 'Please contact system administrator further notification.'
            );
        }
        return $this->redirect($this->generateUrl('restaurant_particular'));
    }

   
    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Particular $entity)
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
        return $this->redirect($this->generateUrl('restaurant_particular'));
    }

    public function sortedAction(Request $request)
    {
        $data = $request ->request->get('item');
        $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->setParticularSorting($data);
        exit;
    }



}
