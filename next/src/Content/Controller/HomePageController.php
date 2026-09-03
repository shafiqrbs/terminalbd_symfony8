<?php

namespace App\Content\Controller;

use App\Content\Entity\HomeBlock;
use App\Content\Entity\PageModule;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use App\Content\Entity\HomePage;
use App\Content\Form\HomePageType;
use Symfony\Component\HttpFoundation\Response;

/**
 * HomePage controller.
 *
 */
class HomePageController extends Controller
{

    /**
     * Lists all HomePage entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        return $this->redirect($this->generateUrl('landing_page'));

    }


    /**
     * Displays a form to edit an existing HomePage entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository(\App\Content\Entity\HomePage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HomePage entity.');
        }

        /** @var  $pageArr */
        $pageArr = array();

        /** @var PageModule $row */
        foreach ($entity->getPageModules() as $row):
            $pageArr[$row->getModule()->getId()] =  $row;
        endforeach;



        $editForm = $this->createEditForm($entity);

        $menus = $this->getDoctrine()->getRepository(\App\Content\Entity\HomeBlock::class)->getMenuLists($entity->getUser());

        return $this->render('Content/HomePage/edit.html.twig', array(
            'entity'      => $entity,
            'menus'      => $menus,
            'globalOption' => $this->getUser()->getGlobalOption(),
            'form'   => $editForm->createView(),
            'pageFeature' => $pageArr
        ));
    }

    /**
     * Displays a form to edit an existing HomePage entity.
     *
     */
    public function modifyAction()
    {

        $em = $this->getDoctrine()->getManager();
        $globalOption = $this->getUser()->getGlobalOption()->getId();
        $entity = $this->getDoctrine()->getManager()->getRepository(\App\Content\Entity\HomePage::class)->findOneBy(array('globalOption'=>$globalOption));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HomePage entity.');
        }

        /** @var  $pageArr */
        $pageArr = array();

        /** @var PageModule $row */
        foreach ($entity->getPageModules() as $row):
            $pageArr[$row->getModule()->getId()] =  $row;
        endforeach;


        // exit(\Doctrine\Common\Util\Debug::dump($entity));
        $icons = $this->getDoctrine()->getManager()->getRepository(\App\Tool\Entity\Icon::class)->findBy(array('status'=>1),array('name'=>'ASC'));
        $editForm = $this->createEditForm($entity);
        $menus = $this->getDoctrine()->getRepository(\App\Content\Entity\HomeBlock::class)->getMenuLists($globalOption);

        return $this->render('Content/HomePage/edit.html.twig', array(
            'entity'      => $entity,
            'menus'      => $menus,
            'icons'      => $icons,
            'globalOption' => $this->getUser()->getGlobalOption(),
            'form'   => $editForm->createView(),
            'pageFeature' => $pageArr
        ));
    }

    /**
    * Creates a form to edit a HomePage entity.
    *
    * @param HomePage $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(HomePage $entity)
    {

        $siteSettingId = $entity->getUser()->getSiteSetting()->getId();
        $globalOption = $this->getUser()->getGlobalOption()->getId();
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into HomePageType::configureOptions() and pass them as form options: $globalOption,$siteSettingId), $entity, array(
        $form = $this->createForm(new HomePageType($globalOption,$siteSettingId), $entity, array(
            'action' => $this->generateUrl('homepage_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            )
        ));

        return $form;
    }
    /**
     * Edits an existing HomePage entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $data = $request->request->all();

        $entity = $em->getRepository(\App\Content\Entity\HomePage::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HomePage entity.');
        }


        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->upload();
            $em->flush();

            $this->getDoctrine()->getRepository(\App\Content\Entity\HomeBlock::class)->insertHomeBlock($data,$entity);
            $this->get('session')->getFlashBag()->add(
                'success',"Data has been changed successfully"
            );
            $this->getDoctrine()->getRepository(\App\Content\Entity\PageModule::class)->createHomeFeatureModule($entity,$data);
            $referer = $request->headers->get('referer');
            return new RedirectResponse($referer);
        }

        return $this->render('Content/HomePage/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView()

        ));
    }

    public function deleteAction(HomeBlock $entity)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        return new Response('success');
    }
}
