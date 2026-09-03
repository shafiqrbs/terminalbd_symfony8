<?php

namespace App\Content\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use App\Content\Entity\PageMeta;
use App\Content\Entity\PageModule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Content\Entity\Page;
use App\Content\Form\PageType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page controller.
 *
 */
class PageController extends AbstractController
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
            20  /*limit per page*/
        );
        return $pagination;
    }


    /**
     * Lists Spacific user  Page entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Content\Entity\Page::class)->getPagesFor($this->getUser()->getGlobalOption(),'page');
        $entities = $this->paginate($entities);

        return $this->render('Content/Page/index.html.twig', array(
            'pagination' => $entities,
        ));
    }

    /**
     * Creates a new Page entity.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */

    public function createAction(Request $request)
    {
        $entity = new Page();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        $data = $request->request->all();
        if ($form->isValid()) {

            $em = $this->em;
            $entity->setUser($this->getUser());
            $entity->setGlobalOption($this->getUser()->getGlobalOption());
            $entity ->setModule($this->em->getRepository(\App\Tool\Entity\Module::class)->findOneBy(array('slug' => 'page')));
            $entity ->upload();
            $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Content\Entity\PageMeta::class)->pageMeta($entity,$data);
            $this->em->getRepository(\App\Appearance\Entity\Menu::class)->createMenuForPage($entity);

            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been inserted successfully"
            );
            return $this->redirect($this->generateUrl('page'));

        }

        return $this->render('Content/Page/new.html.twig', array(
            'entity' => $entity,
            'globalOption' => $this->getUser()->getGlobalOption(),
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Page entity.
     *
     * @param Page $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Page $entity)
    {

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into PageType::configureOptions() and pass them as form options: $this->getUser()->getGlobalOption()), $entity, array(
        $form = $this->createForm(new PageType($this->getUser()->getGlobalOption()), $entity, array(
            'action' => $this->generateUrl('page_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
       return $form;
    }

    /**
     * Displays a form to create a new Page entity.
     *
     */
    public function newAction()
    {
        $entity = new Page();
        $form   = $this->createCreateForm($entity);

        return $this->render('Content/Page/new.html.twig', array(
            'entity' => $entity,
            'globalOption' => $this->getUser()->getGlobalOption(),
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Page entity.
     *
     */
    public function showAction(Page $entity)
    {
        return $this->render('Content/Page/show.html.twig', array(
            'entity'      => $entity

        ));
    }

    /**
     * Displays a form to edit an existing Page entity.
     *
     */
    public function editAction(Page $entity)
    {
        $editForm = $this->createEditForm($entity);

        /** @var  $pageArr */
        $pageArr = array();

        /** @var PageModule $row */
         foreach ($entity->getPageModules() as $row):
             $pageArr[$row->getModule()->getId()] =  $row;
         endforeach;


        return $this->render('Content/Page/new.html.twig', array(
            'entity'      => $entity,
            'globalOption' => $this->getUser()->getGlobalOption(),
            'form'   => $editForm->createView(),
            'pageFeature' => $pageArr
        ));
    }

    /**
     * Creates a form to edit a Page entity.
     *
     * @param Page $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Page $entity)
    {

        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into PageType::configureOptions() and pass them as form options: $entity->getUser()->getGlobalOption()), $entity, array(
        $form = $this->createForm(new PageType($entity->getUser()->getGlobalOption()), $entity, array(
            'action' => $this->generateUrl('page_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));
        return $form;
    }
    /**
     * Edits an existing Page entity.
     *
     */
    public function updateAction(Request $request, Page $entity)
    {
        $em = $this->em;
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $data = $request->request->all();
        if ($editForm->isValid()) {

            if($entity->isRemoveImage() == 1 ){
                $entity->removeUpload();
            }elseif($entity->isRemoveImage() != 1) {
                $entity->upload();
            }
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been updated successfully"
            );
            $this->em->getRepository(\App\Content\Entity\PageMeta::class)->pageMeta($entity,$data);
            $this->em->getRepository(\App\Content\Entity\PageModule::class)->createFeatureModule($entity,$data);
            $this->em->getRepository(\App\Content\Entity\Page::class)->updatePageMenu($entity);

            return $this->redirect($this->generateUrl('page_edit', array('id' => $entity->getId())));
        }

        return $this->render('Content/Page/new.html.twig', array(
            'entity'      => $entity,
            'globalOption' => $this->getUser()->getGlobalOption(),
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a Page entity.
     *
     */
    public function deleteAction(Page $entity)
    {
        $em = $this->em;
        if(!empty($entity->getFile())){
            $entity->removeUpload();
        }
        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add('success',"Data has been updated successfully");
        return $this->redirect($this->generateUrl('page'));
}

    /**
     * Status a Page entity.
     *
     */
    public function statusAction(Request $request, Page $entity)
    {

        $em = $this->em;
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
        return $this->redirect($this->generateUrl('page'));
    }

    public function printDetailsAction(Request $request, Page $page)
    {

        return $this->render('Content/Page/print.html.twig', array(
            'entity'      => $page

        ));

    }
    public function pdfDetailsAction(Request $request, Page $page)
    {

        $html = $this->renderView(
            'Content/Page/print.html.twig', array(
                'entity' => $page
            )
        );


        $wkhtmltopdfPath = '/usr/local/bin/wkhtmltopdf'; /* check mac pdf */
        //$wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver'; /* check server pdf */
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="education.pdf"');
        echo $pdf;

        return new Response('');

    }

    public function metaDeleteAction(PageMeta $entity)
    {
        $em = $this->em;
        $em->remove($entity);
        $em->flush();
        return new Response('success');

    }

}
