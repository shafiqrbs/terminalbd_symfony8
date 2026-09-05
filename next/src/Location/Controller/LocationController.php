<?php

namespace App\Location\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Location\Entity\Location;
use App\Location\Form\LocationType;

/**
 * Location controller.
 *
 */
class LocationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    /**
     * Lists all Location entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Location\Entity\Location::class)->findAll();
        $pagination = $this->paginate($entities);

        return $this->render('Location/Location/index.html.twig', array(
            'pagination' => $pagination,
        ));
    }

    public function paginate($entities)
    {
        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            30  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Creates a new Location entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Location();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('location'));
        }

        return $this->render('Location/Location/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Location entity.
     *
     * @param Location $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Location $entity)
    {
        $em = $this->em->getRepository(\App\Location\Entity\Location::class);

        $form = $this->createForm(LocationType::class, $entity, array('action' => $this->generateUrl('location_create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'em' => $em));

        return $form;
    }

    /**
     * Displays a form to create a new Location entity.
     *
     */
    public function newAction()
    {
        $entity = new Location();
        $form   = $this->createCreateForm($entity);

        return $this->render('Location/Location/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Location entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Location\Entity\Location::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Location entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Location/Location/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Location entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Location\Entity\Location::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Location entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Location/Location/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Location entity.
    *
    * @param Location $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Location $entity)
    {
        $em = $this->em->getRepository(\App\Location\Entity\Location::class);

        $form = $this->createForm(LocationType::class, $entity, array('action' => $this->generateUrl('location_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ), 'em' => $em));

        return $form;
    }
    /**
     * Edits an existing Location entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Location\Entity\Location::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Location entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('location_edit', array('id' => $id)));
        }

        return $this->render('Location/Location/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Location entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->em;
            $entity = $em->getRepository(\App\Location\Entity\Location::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Location entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('location'));
    }

    /**
     * Creates a form to delete a Location entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('location_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }

    public function autoSearchAction(Request $request)
    {
        $item = $_REQUEST['q'];
        if ($item) {
            $item = $this->em->getRepository(\App\Location\Entity\Location::class)->searchAutoComplete($item);
        }
        return new JsonResponse($item);
    }

    public function searchNameAction($grn)
    {
        return new JsonResponse(array(
            'id'=>$grn,
            'text'=>$grn
        ));
    }

}
