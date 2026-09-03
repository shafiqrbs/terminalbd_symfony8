<?php

namespace App\Media\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use App\Media\Entity\GalleryImage;
use App\Media\Form\GalleryImageType;


/**
 * GalleryImage controller.
 *
 */
class GalleryImageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all GalleryImage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Media\Entity\GalleryImage::class)->findAll();

        return $this->render('Media/GalleryImage/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    public function uploadAction($id)
    {
        $globalOption = $this->getUser()->getGlobalOption()->getId();
        $entity = new GalleryImage();
        $entity ->upload($globalOption,$id);
    }

    /**
     * Creates a new GalleryImage entity.
     *
     */
    public function createAction(Request $request)
    {

        $data = $request->request->all();
        $galleryId = $request->request->get('galleryId');

        $this->em->getRepository(\App\Media\Entity\GalleryImage::class)->insertGalleryImage($data,$galleryId);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Data has been inserted successfully"
        );
        return $this->redirect($this->generateUrl('galleryimage_edit', ['id' => $galleryId]));

    }

    /**
     * Creates a form to create a GalleryImage entity.
     *
     * @param GalleryImage $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(GalleryImage $entity)
    {

        $form = $this->createForm(new GalleryImageType(), $entity, [
            'action' => $this->generateUrl('galleryimage_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);

        return $form;

    }

    /**
     * Displays a form to create a new GalleryImage entity.
     *
     */
    public function newAction(Request $request)
    {
        $em = $this->em;
        $galleryImageId =  $this->requestStack->getCurrentRequest()->get('galleryId');

        $entity = new GalleryImage();
        $form   = $this->createCreateForm($entity);
        $globalOption = $this->getUser()->getGlobalOption();
        $entity = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->findOneBy(['id'=>$galleryImageId,'globalOption'=>$globalOption]);

        if(null === $galleryImageId || empty($entity) ) {

            return $this->redirect($this->generateUrl('gallery'));

        }else{

            return $this->render('Media/GalleryImage/new.html.twig', [
                'entity' => $entity,
                'form'   => $form->createView(),
            ]);
        }

    }

    /**
     * Displays a form to edit an existing GalleryImage entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;


        $globalOption = $this->getUser()->getGlobalOption();

        $entityGallery = $em->getRepository(\App\Media\Entity\PhotoGallery::class)->findOneBy(['id'=>$id ,'globalOption' => $globalOption]);
        if(empty($entityGallery) ) {

            return $this->redirect($this->generateUrl('gallery'));
        }

        $entity = $em->getRepository(\App\Media\Entity\GalleryImage::class)->findBy(['photoGallery'=>$id]);

        if (!$entity) {

            return $this->redirect($this->generateUrl('galleryimage_new', ['galleryId' => $id]));

        }

        $editForm = $this->createEditForm($id);
        return $this->render('Media/GalleryImage/edit.html.twig', [
            'entitities'      => $entity,
            'gallery'      => $entityGallery,
            'edit_form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a GalleryImage entity.
     *
     * @param GalleryImage $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm($id)
    {
        $form = $this->createForm(new GalleryImageType(), null, [
            'action' => $this->generateUrl('galleryimage_update', ['id' => $id]),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;

    }
    /**
     * Edits an existing GalleryImage entity.
     *
     */
    public function updateAction(Request $request, $id)
    {

        $data = $request->request->all();
        $this->em->getRepository(\App\Media\Entity\GalleryImage::class)->updateGalleryImage($data);

        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Data has been updated successfully"
        );
        return $this->redirect($this->generateUrl('galleryimage_edit', ['id' => $id]));

    }

    public function deleteImageAction(GalleryImage $entity)
    {

        $em = $this->em;
        if(!empty($entity)){
            $entity->removeUpload($entity->getPhotoGallery()->getGlobalOption()->getId(),$entity->getPhotoGallery()->getId());
            $em->remove($entity);
            $em->flush();
        }
        exit;

    }


}
