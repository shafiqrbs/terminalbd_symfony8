<?php
/*
 * This file is part of the Docudex project.
 *
 * (c) Devnet Limited <http://www.devnetlimited.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User\Controller;

use App\User\Form\UserType;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\User\Entity\User;
use App\User\Form\Type\UserNotificationFormType;
use App\User\Manager\UserManager;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageUserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly UserManager $userManager,
        private readonly UserType $userType,
    ) {
    }

    public function indexAction(Request $request, $page)
    {
        $request->query->set('page', $page);

        return $this->render('User/Users/index.html.twig', [
            'pagination' =>  $this->getPaginateData()
        ]);
    }

    private function getPaginateData()
    {
        $query = $this->getUserManager()->getUserQuery();

        $paginator = $this->paginator;

        return $paginator->paginate(
            $query,
            $this->requestStack->getCurrentRequest()->query->get('page', 1),
            10,
            ['distinct'=>false]
        );
    }

    public function createAction(Request $request)
    {
        $userManager = $this->getUserManager();

        $user = new User();//$userManager->createUser();

	    // TODO(migration): AbstractController::get() was removed; inject this service.
	    $service = $this->userType;

        $form = $this->createForm($service, $user);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setEnabled(true);
                $userManager->updateUser($user);

                $this->requestStack->getSession()->getFlashBag()->add(
                    'user-success',
                    'User Created Successfully!'
                );

                return $this->redirect($this->generateUrl('docudex_user_manager'));
            }
        }

        return $this->render('User/Users/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function editAction(Request $request, User $user)
    {
        $userManager = $this->getUserManager();

	    // TODO(migration): AbstractController::get() was removed; inject this service.
	    $service = $this->userType;
	    $form = $this->createForm($service, $user);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $user->isEnabled();
                $userManager->updateUser($user);

                $this->requestStack->getSession()->getFlashBag()->add(
                    'user-success',
                    'User Updated Successfully!'
                );

                return $this->redirect($this->generateUrl('docudex_user_manager'));
            }
        }

        return $this->render('User/Users/edit.html.twig', [
            'form'      => $form->createView(),
            'user_id'   => $user->getId(),
            'user_name' => $user->getUsername()
        ]);
    }

    /**
     * @JMS\Secure(roles="ROLE_USER_DELETE")
     */
    public function deleteAction(Request $request, User $user)
    {
        $userManager = $this->getUserManager();

        $submittedData = $request->request->all();

        if($user->getId() != $submittedData['id']){
            throw $this->createNotFoundException('Page not found');
        }

        if ('POST' === $request->getMethod()) {

            if ($this->isCsrfTokenValid('delete_action_' . $submittedData['id'], $submittedData['_token'])) {

                $this->deleteAvatar($user);
                $userManager->deleteUser($user);

                $this->requestStack->getSession()->getFlashBag()->add(
                    'user-success',
                    'User Deleted Successfully!'
                );
            } else {
                $this->requestStack->getSession()->getFlashBag()->add(
                    'user-error',
                    'Delete failed! Please Resubmit your form'
                );
            }

            return $this->redirect($this->generateUrl('system_user_manager'));
        }

        throw $this->createNotFoundException('Page not found');
    }

    public function checkDuplicateAction(Request $request)
    {


        $usernameOrEmail = $request->request->get('type', NULL);


        $userManager = $this->getUserManager();

        $user = $userManager->findUserByUsernameOrEmail($usernameOrEmail);

        if ($user && $user->getId() != $request->request->get("user_id")) {
            $response = 'false';
        } else {
            $response = 'true';
        }

        return new Response($response);
    }

    public function checkMobileDuplicateAction(Request $request)
    {

        $data = $request->request->all();

        $user = $this->findUserByMobile($data['mobile']);
        if ($user && $user->getId() != $data['profile_id'] ) {
            $response = 'false';
        } else {
            $response = 'true';
        }
        return new Response($response);
    }

    public function findUserByMobile($mobile)
    {
       $profile = $this->em->getRepository(\App\User\Entity\Profile::class)->findBy(['mobile'=>$mobile]);
        if(!empty($profile)){
            return $profile->getId();
        }
        return false;
    }

    public function avatarAction(Request $request)
    {

        if($request->getMethod() == 'POST')
        {
            $user = $this->getUser();
            $csrf = 'update_avatar_action_' . $user->getId();

            if ($this->isCsrfTokenValid($csrf, $request->request->get('_token'))) {

                if ($this->uploadAvatar($request->files->get('avatar'), $user)) {

                    $this->requestStack->getSession()->getFlashBag()->add(
                        'user-success',
                        'New Avatar saved Successfully!'
                    );

                    $this->getUserManager()->updateUser($user);
                }
            }
        }

        return $this->render('DocudexUserBundle:Avatar:edit.html.twig');
    }

    public function notificationAction(Request $request)
    {
        $user = $this->getUser();

        $form = $this->createForm(UserNotificationFormType::class, $user);

        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $userManager = $this->getUserManager();
                $userManager->updateUser($user);

                $this->requestStack->getSession()->getFlashBag()->add(
                    'user-success',
                    'Settings Updated Successfully!'
                );
            }
        }

        return $this->render('DocudexUserBundle:Profile:notification_settings.html.twig', [
            'form' => $form->createView()
        ]);
    }


    #[IsGranted('ROLE_MANAGE_USERS')]
    public function changeUserPasswordAction(Request $request, User $user)
    {
        $val = $user->getPassword();

        if(empty($val)) {
            throw $this->createNotFoundException('Page not found');
        }

        $plainPassword = $this->isValidFormDataForChangePassword($request->request, $user->getId());

        $response = $this->redirect($this->getUserListUrl($request->request->get('page')));

        if ($plainPassword) {
            $this->getUserManager()->changeUserPassword($user, $plainPassword);


            $this->requestStack->getSession()->getFlashBag()->add(
                'user-success',
                'Password updated successfully!!'
            );
        } else {
            $this->requestStack->getSession()->getFlashBag()->add(
                'user-error',
                'Password updated failed! Please Resubmit your form'
            );
        }

        return $response;
    }

    protected function isValidFormDataForChangePassword(ParameterBag $request, $userId)
    {
        if (!$this->isCsrfTokenValid('password_change_' . $userId, $request->get('_token'))) {
            return FALSE;
        }

        $plainPassword = $request->get('password');
        $confirmPassword = trim($request->get('confirm_password'));

        if ($plainPassword === $confirmPassword && strlen($confirmPassword) >= 6) {
            return $plainPassword;
        }

        return FALSE;
    }

    protected function getUserListUrl($page = 1)
    {
        return $this->generateUrl(
            'docudex_user_manager',
            ['page'=>$page]
        );
    }

    private function uploadAvatar($file, User $user)
    {
        if(empty($file)) {
           return false;
        }

        // TODO(migration): AbstractController::get() was removed; inject this service.
        $uploader = $this->get('docudex.core.service.uploader');

        $uploadedFileInfo = $uploader
            ->setUploadPathKey('user.profile.image')
            ->upload($file);

        if ($uploadedFileInfo) {

            $this->deleteAvatar($user, $uploader->getUploadPath());
            $user->setAvatar($uploadedFileInfo['filename']);

            return $uploadedFileInfo;
        }

        return false;
    }

    private function deleteAvatar(User $user, $baseDire = null)
    {
        $uploadDir = $this->getAvatarBaseDirectory($baseDire);

        if ($user->getAvatar() != "" && file_exists($uploadDir . $user->getAvatar())) {
            unlink($uploadDir . $user->getAvatar());
        }
    }

    private function getAvatarBaseDirectory($baseDire = null)
    {
        if($baseDire == null) {
            // TODO(migration): AbstractController::get() was removed; inject this service.
            $pathConfigurator = $this->get('docudex.core.service.path.configurator');
            $baseDire = $pathConfigurator->getPathFromKey('user.profile.image', true);
        }

        if (substr($baseDire, -1, 1) != DIRECTORY_SEPARATOR) {
            $baseDire = $baseDire . DIRECTORY_SEPARATOR;
        }

        return $baseDire;
    }

	private function getUserManager(): UserManager
	{
		return $this->userManager;
	}
}