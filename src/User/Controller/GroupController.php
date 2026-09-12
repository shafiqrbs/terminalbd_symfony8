<?php

declare(strict_types=1);

/*
 * This file is part of the Docudex project.
 *
 * (c) Devnet Limited <http://www.devnetlimited.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User\Controller;

use App\User\Entity\Group;
use App\User\Form\Type\GroupFormType;
use App\User\Manager\GroupManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Was an override of FOSUserBundle's GroupController.
 *
 * Three Symfony 2 constructs are gone with it: the `templating` service
 * (removed in Symfony 5) is now $this->render(), $form->bind($request) is
 * handleRequest(), and the group form comes from the app's own GroupFormType
 * under the name FOSUserBundle's factory gave it.
 */
class GroupController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FormFactoryInterface $formFactory,
        private readonly GroupManager $groupManager,
    ) {
    }

    public function listAction(): Response
    {
        return $this->render('User/Group/list.html.twig', [
            'groups' => $this->groupManager->findGroups(),
        ]);
    }

    public function newAction(Request $request): Response
    {
        $group = $this->groupManager->createGroup('');

        $form = $this->createGroupForm($group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->groupManager->updateGroup($group);

            $this->requestStack->getSession()->getFlashBag()->add(
                'success-status',
                'Group Created Successfully!'
            );

            return new RedirectResponse($this->generateUrl('fos_user_group_list'));
        }

        return $this->render('User/Group/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function editByIdAction(Request $request, Group $group): Response
    {
        $form = $this->createGroupForm($group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->groupManager->updateGroup($group);

            $this->requestStack->getSession()->getFlashBag()->add(
                'success-status',
                'Group Updated Successfully!'
            );

            return new RedirectResponse($this->generateUrl('fos_user_group_list'));
        }

        return $this->render('User/Group/edit.html.twig', [
            'form' => $form->createView(),
            'group' => $group,
        ]);
    }

    #[IsGranted('ROLE_GROUP_DELETE')]
    public function deleteByIdAction(Request $request, Group $group): Response
    {
        $this->groupManager->deleteGroup($group);

        $this->requestStack->getSession()->getFlashBag()->add(
            'success-status',
            'Group Deleted Successfully!'
        );

        return new RedirectResponse($this->generateUrl('fos_user_group_list'));
    }

    #[IsGranted('ROLE_MANAGE_PERMISSION')]
    public function categoryAccessAction(Request $request, Group $group): Response
    {
        set_time_limit(0);

        if ($group->hasRole('ROLE_MANAGE_GROUPS')) {
            throw new NotFoundHttpException("Page not found");
        }

        if ($request->getRealMethod() == 'POST') {

            $this->requestStack->getSession()->getFlashBag()->add(
                'success-status',
                'Group Category Updated Successfully!'
            );

            $categories = $request->request->get('category');

            $this->updateGroupCategories($group, $categories);

            return new RedirectResponse($this->generateUrl('fos_user_group_list'));
        }

        $categories = [];//$this->getTermManager()->getCategoryTree();

        return $this->render('User/Group/category-access.html.twig', [
            'group' => $group->getName(),
            'categories' => $categories,
            'selectedCategories' => $this->getCategoryIds($group),
        ]);
    }

    private function createGroupForm(Group $group)
    {
        return $this->formFactory->createNamed('fos_user_group_form', GroupFormType::class, $group);
    }

    protected function getCategoryIds(Group $group)
    {
        $ids = [];

        foreach($group->getCategories() as $category)
        {
            $ids[] = $category->getTermId();
        }

        return $ids;
    }

    protected function updateGroupCategories(Group $group, $categories = [])
    {
        $categories = [];//$this->getTermManager()->getAllTermsByIds($categories);

        $groupCategories = $group->getCategories();

        $groupCategories->clear();

        foreach ($categories as $category) {
            $groupCategories->add($category);
        }

        $this->groupManager->updateGroup($group);
    }
}
