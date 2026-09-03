<?php

namespace App\Media\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Media\Entity\PhotoGallery;
use App\Media\Form\PhotoGalleryType;

/**
 * PhotoGallery controller.
 *
 */
class PhotoGalleryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    /**
     * Lists all PhotoGallery entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->findBy(array('globalOption' => $globalOption),array('created'=>'desc'));

        $pagination = $this->paginate($entities);

        return $this->render('Media/PhotoGallery/index.html.twig', array(
            'pagination' => $pagination
        ));

    }


    public function galleriesDeleteList()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->findAll();

        $pagination = $this->paginate($entities);

        return $this->render('Media/PhotoGallery/index.html.twig', array(
            'pagination' => $pagination
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
     * Lists all PhotoGallery entities.
     *
     */
    public function galleryAction()
    {
        $em = $this->em;

        $globalOption = $this->getUser()->getGlobalOption()->getId();
        $entities = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->findBy(array('globalOption' => $globalOption ),array('created'=>'desc'));

        return $this->render('Media/PhotoGallery/index.html.twig', array(
            'pagination' => $entities,
        ));
    }

    /**
     * Lists all PhotoGallery entities.
     *
     */
    public function galleryDeleteAction()
    {
        $em = $this->em;

        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->findBy(array('globalOption' => $globalOption ),array('created'=>'desc'));
        return $this->render('Media/PhotoGallery/index.html.twig', array(
            'pagination' => $entities,
        ));
    }


    /**
     * Creates a new PhotoGallery entity.
     *
     */
    public function createAction(Request $request)
    {

        $entity = new PhotoGallery();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $globalOption = $this->getUser()->getGlobalOption();

        if ($form->isValid()) {
            $em = $this->em;

            $entity->setGlobalOption($globalOption);
            $entity->upload();
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            $this->createDirectory($entity->getId());
            return $this->redirect($this->generateUrl('gallery'));
        }

        return $this->render('Media/PhotoGallery/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    public function createDirectory($gallery, $dir = null)
    {
        $globalOption = $this->getUser()->getGlobalOption()->getId();
        $assets_dir = __DIR__.'/../../../../../web/uploads/domain/';
        if(file_exists($assets_dir.$globalOption)){
            mkdir($assets_dir.$globalOption.'/media/'.$gallery, 0777);
        }else{
            return false;
        }
    }


    /**
     * Creates a form to create a PhotoGallery entity.
     *
     * @param PhotoGallery $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(PhotoGallery $entity)
    {

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into PhotoGalleryType::configureOptions() and pass them as form options: $this->getUser()->getGlobalOption()), $entity, array(
        $form = $this->createForm(new PhotoGalleryType($this->getUser()->getGlobalOption()), $entity, array(
            'action' => $this->generateUrl('gallery_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));

        return $form;
    }

    /**
     * Displays a form to create a new PhotoGallery entity.
     *
     */
    public function newAction()
    {
        $entity = new PhotoGallery();
        $form   = $this->createCreateForm($entity);

        return $this->render('Media/PhotoGallery/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a PhotoGallery entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PhotoGallery entity.');
        }
        return $this->render('Media/PhotoGallery/show.html.twig', array(
            'entity'      => $entity,

        ));
    }

    /**
     * Displays a form to edit an existing PhotoGallery entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PhotoGallery entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Media/PhotoGallery/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }

    /**
    * Creates a form to edit a PhotoGallery entity.
    *
    * @param PhotoGallery $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(PhotoGallery $entity)
    {

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into PhotoGalleryType::configureOptions() and pass them as form options: $this->getUser()->getGlobalOption()), $entity, array(
        $form = $this->createForm(new PhotoGalleryType($this->getUser()->getGlobalOption()), $entity, array(
            'action' => $this->generateUrl('gallery_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;

    }
    /**
     * Edits an existing PhotoGallery entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find PhotoGallery entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->upload();
            $em->flush();

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            return $this->redirect($this->generateUrl('gallery_edit', array('id' => $id)));
        }

        return $this->render('Media/PhotoGallery/new.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),

        ));
    }
    /**
     * Deletes a PhotoGallery entity.
     *
     */
    public function deleteAction(PhotoGallery $entity)
    {

	    $em = $this->em;
	    if (!$entity) {
		    throw $this->createNotFoundException('Unable to find Product entity.');
	    }

	    try {
		    $entity->deleteImageDirectory();
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
	    return $this->redirect($this->generateUrl('gallery'));
    }




    /**
     * Status a PhotoGallery entity.
     *
     */
    public function statusAction(Request $request, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->find($id);

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
            'error',"Status has been changed successfully"
        );
        return $this->redirect($this->generateUrl('gallery'));
    }


}
