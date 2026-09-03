<?php

namespace App\Appearance\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use App\Appearance\Entity\MenuGrouping;
use App\Appearance\Form\MenuGroupingType;

/**
 * MenuGrouping controller.
 *
 */
class MenuGroupingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    /**
     * Lists all MenuGrouping entities.
     *
     */
    public function indexAction()
    {
        /** @var EntityManager $em */

        $entities = $this->em->getRepository(\App\Appearance\Entity\MenuGroup::class)->findBy(['status'=>1],['name'=>'ASC']);
        return $this->render('Appearance/MenuGrouping/index.html.twig', [
            'entities' => $entities
        ]);
    }

    /**
     * Creates a new MenuGrouping entity.
     *
     */
    public function createAction(Request $request)
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();

        $posts = $request->request->all();

        $entity = new MenuGrouping();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $menuGroup = $request->request->get('menuGroup');
            $this->em->getRepository(\App\Appearance\Entity\MenuGrouping::class)->insertMenuGrouping($posts,$globalOption,$menuGroup);
            return $this->redirect($this->generateUrl('menugrouping_sorting', ['menuGroup' => $menuGroup ]));
        }

        return $this->render('Appearance/MenuGrouping/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to create a MenuGrouping entity.
     *
     * @param MenuGrouping $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(MenuGrouping $entity)
    {

        $form = $this->createForm(new MenuGroupingType(), $entity, [
            'action' => $this->generateUrl('menugrouping_create', ['id' => $entity->getId()]),
            'method' => 'POST',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    /**
     * Displays a form to create a new MenuGrouping entity.
     *
     */
    public function newAction($id)
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();
        $menuGroup = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->find($id);
        $entities = $em->getRepository(\App\Appearance\Entity\Menu::class)->findBy(['globalOption' => $globalOption,'status'=>1]);

        $grouping = $em->getRepository(\App\Appearance\Entity\MenuGrouping::class)->findBy(['globalOption' => $globalOption,'menuGroup'=>$id]);

        if(empty($grouping)){

            $entity = new MenuGrouping();
            $form   = $this->createCreateForm($entity);

            return $this->render('Appearance/MenuGrouping/new.html.twig', [
                'entity' => $entity,
                'entities' => $entities,
                'menuGroup' => $menuGroup,
                'form'   => $form->createView(),
            ]);

        }else{

            return $this->redirect($this->generateUrl('menugrouping_edit', ['id' => $id]));
        }

    }


    /**
     * Finds and displays a MenuGrouping entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Appearance\Entity\MenuGrouping::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MenuGrouping entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Appearance/MenuGrouping/show.html.twig', [
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing MenuGrouping entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->em;
        $groupings =[];


        $globalOption = $this->getUser()->getGlobalOption();

        $menuGroup = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->find($id);

        $entities = $em->getRepository(\App\Appearance\Entity\Menu::class)->findBy(['globalOption' => $globalOption,'status'=>1]);

        $grouping = $em->getRepository(\App\Appearance\Entity\MenuGrouping::class)->findBy(['globalOption' => $globalOption,'menuGroup'=>$id]);
        foreach($grouping as $row){
            $groupings[] = $row->getMenu()->getId();
        }

        $entity = new MenuGrouping();
        $editForm   = $this->createCreateForm($entity);

        return $this->render('Appearance/MenuGrouping/edit.html.twig', [
            'entities'      => $entities,
            'form'          => $editForm->createView(),
            'menuGroup'     => $menuGroup,
            'groupings'     => $groupings,

        ]);

    }

    public function sortingAction($menuGroup)
    {
        $em = $this->em;
        $globalOption = $this->getUser()->getGlobalOption();

        $menus = $em->getRepository(\App\Appearance\Entity\MenuGrouping::class)->findBy(['globalOption'=>$globalOption,'parent'=>NULL,'menuGroup'=>$menuGroup],['sorting'=>'asc']);
        $menusTree = $this->em->getRepository(\App\Appearance\Entity\MenuGrouping::class)->getMenuTree($menus);
        $menuGroup = $em->getRepository(\App\Appearance\Entity\MenuGroup::class)->find($menuGroup);

        return $this->render('Appearance/MenuGrouping/sorting.html.twig', [
            'menu' => $menusTree,
            'menuGroup' => $menuGroup,


        ]);

    }

    public function sortedAction(Request $request)
    {
        $data = $request ->request->get('menuItem');
        $this->em->getRepository(\App\Appearance\Entity\MenuGrouping::class)->setMenuOrdering($data);
        exit;

    }


}


