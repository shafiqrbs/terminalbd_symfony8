<?php

namespace App\Appearance\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Appearance\Entity\Menu;
use App\Appearance\Form\MenuEditType;
use App\Appearance\Form\MenuType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Menu controller.
 *
 */
class MenuController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }


    /**
     * Lists all GlobalOption entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->findAll();

        return $this->render('Appearance/Menu/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Lists all Menu manage entities.
     *
     */
    public function menuManageAction()
    {
        $em = $this->em;
        $id = $this->getUser()->getGlobalOption()->getId();
        $entities = $em->getRepository(\App\Appearance\Entity\Menu::class)->findBy(['globalOption'=>$id]);
        $form = $this->createEditForm($id);
        return $this->render('Appearance/Menu/edit.html.twig', [
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a new MenuGroup entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Menu();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $option = $this->getUser()->getGlobalOption();
            $entity->setGlobalOption($option);
            $entity->setMode("custom");
            $em->persist($entity);
            $em->flush();
            return $this->redirect($this->generateUrl('menu_manage'));
        }

        return $this->render('Appearance/Menu/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a MenuGroup entity.
     *
     * @param Menu $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Menu $entity)
    {
        $form = $this->createForm(MenuType::class, $entity, [
            'action' => $this->generateUrl('menu_create'),
            'method' => 'POST',
            'attr' => [
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new Menu entity.
     *
     */
    public function newAction()
    {
        $entity = new Menu();
        $form   = $this->createCreateForm($entity);
        return $this->render('Appearance/Menu/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }


    /**
     * Lists all menu modify entities.
     *
     */
    public function modifyAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $em->getRepository(\App\Appearance\Entity\Menu::class)->findBy(['globalOption'=>$globalOption],['sorting'=>'asc']);
        $form = $this->createEditForm($globalOption->getMenu());
        return $this->render('Appearance/Menu/edit.html.twig', [
            'entities' => $entities,
            'form'   => $form->createView(),
        ]);
    }


    /**
     * Creates a form to edit a Menu entity.
     *
     * @param Menu $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm($id)
    {
        $form = $this->createForm(MenuEditType::class, null, [
            'action' => $this->generateUrl('menu_update', ['id' => $id]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }


    /**
     * Edits an existing MenuGroup entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $data = $request->request->all();
        $this->em->getRepository(\App\Appearance\Entity\Menu::class)->updateMenu($data);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Status has been changed successfully"
        );
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }


    /**
     * Status a Page entity.
     *
     */
    public function stopMenuAction(Request $request, $id)
    {


        $em = $this->em;
        $entity = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($id);

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
        return $this->redirect($this->generateUrl('menu'));
    }

    /**
     * Status a Page entity.
     *
     */
    public function statusAction( $globalOption, $id)
    {

        $em = $this->em;
        $entity = $em->getRepository(\App\Appearance\Entity\Menu::class)->findOneBy(['globalOption'=>$globalOption,'id'=>$id]);

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
        return $this->redirect($this->generateUrl('menu_manage'));
    }

    public function resetMenuAction()
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $globalOption->getId();
        $entities = $em->getRepository(\App\Appearance\Entity\MenuCustom::class)->findAll();
        foreach( $entities as $custom){

            $exist = $em->getRepository(\App\Appearance\Entity\Menu::class)->findOneBy(['globalOption'=>$globalOption,'menuCustom' => $custom->getId()]);

            if(empty($exist)){
                $menu = new Menu();
                $menu->setGlobalOption($globalOption);
                $menu->setMenuCustom($custom);
                $menu->setMenu($custom->getMenu());
                $menu->setMenuSlug($globalOption->getSlug().'-'.$custom->getSlug());
                $menu->setSlug($custom->getSlug());
                $menu->setStatus(0);
                $em->persist($menu);
                $em->flush($menu);
            }
        }
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Reset menu has been updated successfully"
        );
        return $this->redirect($this->generateUrl('menu_manage'));
    }





}
