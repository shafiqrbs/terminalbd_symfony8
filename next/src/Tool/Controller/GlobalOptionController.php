<?php

namespace App\Tool\Controller;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Tool\Service\ToolManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\User\Entity\User;
use App\User\Form\SignupType;
use App\Tool\Form\GlobalOptionModifyType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Tool\Entity\GlobalOption;
use App\Tool\Form\GlobalOptionType;
use Symfony\Component\HttpFoundation\Response;

/**
 * GlobalOption controller.
 *
 */
class GlobalOptionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly ToolManager $toolManager,
        private readonly EventDispatcherInterface $eventDispatcher,
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
     * Lists all GlobalOption entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;

        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->findAll();
        $entities = $this->paginate($entities);
        return $this->render('Tool/GlobalOption/index.html.twig', array(
            'pagination' => $entities,
        ));
    }
    /**
     * Creates a new GlobalOption entity.
     *
     */
    public function createAction(Request $request)
    {

        $entity = new User();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $intlMobile = $entity->getProfile()->getMobile();
        $mobile = $this->toolManager->specialExpClean($intlMobile);
        $entity->getProfile()->setMobile($mobile);
        $data = $request->request->all();
        $exist = $this->em->getRepository(\App\User\Entity\User::class)->checkExistingUser($mobile);
        if ($form->isSubmitted() && $form->isValid() and $exist == false) {
            $user = $this->getUser();
            $globalOption = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->createGlobalOption($mobile,$data,$user);
            $entity->setPlainPassword("*148148#");
            $entity->setGlobalOption($globalOption);
            $entity->setEnabled(true);
            $entity->setDomainOwner(true);
            $entity->setUsername($mobile);
            $entity->setEmail($mobile.'@gmail.com');
            $entity->setRoles(array('ROLE_DOMAIN'));
            $em = $this->em;
            $em->persist($entity);
            $em->flush();
            $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->initialSetup($entity);
            $this->toolManager->createDirectory($entity->getGlobalOption()->getId());
          //  $dispatcher = $this->eventDispatcher;
        //    $dispatcher->dispatch(new \App\Tool\Event\UserSignup($entity), 'setting_tool.post.user_signup_msg');
            return $this->redirect($this->generateUrl('globaloption_edit', array('id' => $globalOption->getId())));
        }

        $this->requestStack->getSession()->getFlashBag()->add(
            'notice',"The mobile no have to be unique"
        );

        return $this->render('Tool/GlobalOption/signup.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));

    }

    private function createCreateForm(User $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $em = $this->em->getRepository(\App\Tool\Entity\Syndicate::class);
        $form = $this->createForm(SignupType::class, $entity, array('action' => $this->generateUrl('globaloption_create', array('id' => $entity->getId())),
            'method' => 'POST',
            'attr' => array(
                'id' => 'signup',
                'class' => 'form-horizontal  registration signupForm',
                'novalidate' => 'novalidate',
            ), 'em' => $em, 'location' => $location));

        return $form;
    }


    /**
     * Displays a form to create a new GlobalOption entity.
     *
     */
    public function newAction()
    {
        $entity = new User();
        $form   = $this->createCreateForm($entity);
        return $this->render('Tool/GlobalOption/signup.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a GlobalOption entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GlobalOption entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Tool/GlobalOption/show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing GlobalOption entity.
     *
     */
    public function editAction(GlobalOption $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GlobalOption entity.');
        }
        $this->toolManager->createDirectory($entity->getId());
        $this->em->getRepository(\App\Tool\Entity\SiteSetting::class)->globalOptionSetting($entity);

        $subSyndicates = $this->getSubSyndicateUnderVendor($entity);
        $editForm = $this->createEditForm($entity);

        return $this->render('Tool/GlobalOption/new.html.twig', array(
            'entity'      => $entity,
            'subSyndicates'      => $subSyndicates,
            'edit_form'   => $editForm->createView(),
        ));
    }

    public function hardResetSystemDataAction(GlobalOption $option)
    {
        $em = $this->em;

        set_time_limit(0);

        if($option->getInventoryConfig()) {
            $this->em->getRepository(\App\Hospital\Entity\HospitalConfig::class)->hospitalDelete($option);
        }
        if($option->getInventoryConfig()) {
            $this->em->getRepository(\App\Inventory\Entity\InventoryConfig::class)->inventoryReset($option);
        }
        if($option->getMedicineConfig()) {
            $this->em->getRepository(\App\Medicine\Entity\MedicineConfig::class)->medicineDelete($option);
        }
        if($option->getBusinessConfig()) {
            $this->em->getRepository(\App\Business\Entity\BusinessConfig::class)->businessDelete($option);
        }
        if($option->getRestaurantConfig()) {
            $this->em->getRepository(\App\Restaurant\Entity\RestaurantConfig::class)->delete($option);
        }

        if($option->getAccountingConfig()){
            $this->em->getRepository(\App\Accounting\Entity\AccountingConfig::class)->accountingDeleteReset($option);
        }

        $account = $em->createQuery("DELETE App\\User\\Entity\\User e WHERE e.globalOption = {$option->getId()} and e.domainOwner !=1 and e.username != {$option->getMobile()}");
        if($account){
            $account->execute();
        }

        $deletecustomer = $em->createQuery("DELETE App\\DomainUser\\Entity\\Customer a WHERE a.globalOption = {$option->getId()} and a.name != 'Default' and a.mobile != {$option->getMobile()}");
        if($deletecustomer){
            $deletecustomer->execute();
        }
        $existOption = $this->em->getRepository(GlobalOption::class)->findBy(array('hotline'=>$option->getHotline()));
        if($option->getHotline() and count($existOption) == 1){
            $masterUser = $this->em->getRepository(\App\User\Entity\User::class)->findOneBy(array('globalOption'=>$option->getId(),'domainOwner'=>1,'username'=>$option->getMobile()));
            if($masterUser){
                $masterUser->setUsername($option->getHotline());
                $masterUser->setUsernameCanonical($option->getHotline());
            }
            $masterCustomer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('globalOption'=>$option,'name'=>"Default",'mobile'=>$option->getMobile()));
            if($masterCustomer){
                $masterCustomer->setMobile($option->getHotline());
            }
            $option->setMobile($option->getHotline());
            $option->setCreated(new \DateTime("now"));
            $option->setUpdated(new \DateTime("now"));
            $em->flush();
        }
        $dir = WEB_PATH . "/uploads/domain/{$option->getId()}";
        $a = new Filesystem();
        $a->remove($dir);
        $a->mkdir($dir);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Successfully reset data"
        );
        return $this->redirect($this->generateUrl('globaloption_edit', array('id' => $option->getId())));
    }

    /**
    * Creates a form to edit a GlobalOption entity.
    *
    * @param GlobalOption $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(GlobalOption $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(GlobalOptionType::class, $entity, array('action' => $this->generateUrl('globaloption_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'location' => $location));

        return $form;

    }



    /**
     * Edits an existing GlobalOption entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GlobalOption entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity->upload();
            $em->flush();
            //$this->em->getRepository(\App\Content\Entity\HomePage::class)->globalOptionHome($user);
            //$this->em->getRepository(\App\Content\Entity\ContactPage::class)->globalOptionContact($user);
            $this->em->getRepository(\App\Tool\Entity\SiteSetting::class)->updateSettingMenu($entity);
            $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->systemConfigUpdate($entity);
            $this->requestStack->getSession()->getFlashBag()->add('success',"Data has been updated successfully");

            /*$referer = $request->headers->get('referer');
            return new RedirectResponse($referer);*/
            return $this->redirect($this->generateUrl('globaloption_edit', array('id' => $entity->getId())));


        }
        return $this->render('Tool/GlobalOption/new.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));


    }
    /**
     * Deletes a GlobalOption entity.
     *
     */
    public function deleteAction(GlobalOption $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GlobalOption entity.');
        }

        $em->remove($entity);
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add('error',"Data has been deleted successfully");
        return $this->redirect($this->generateUrl('tools_domain'));
    }

     /**
     * Reset a GlobalOption entity.
     *
     */
    public function resetLogoAction(Request $request,GlobalOption $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GlobalOption entity.');
        }
        $entity->removeUpload();
        $entity->getTemplateCustomize()->removeLogo();
        $entity->getTemplateCustomize()->setLogo(null);
        $template =  $entity->getTemplateCustomize();
        if(!empty($template->getLogo())){
            $template->removeLogo();
            $template->setLogo(NULL);
        }
        if(!empty($template->getFavicon())){
            $template->removeFavicon();
            $template->setFavicon(NULL);
        }
        if(!empty($template->getHeaderBgImage())){
            $template->removeHeaderImage();
            $template->setHeaderBgImage(NULL);
        }
        if(!empty($template->getBgImage())){
            $template->removeBodyImage();
            $template->setBgImage(NULL);
        }
        if(!empty($template->getAndroidLogo())){
            $template->removeAndroidLogo();
            $template->setAndroidLogo(NULL);
        }
        $em->flush();
        $this->requestStack->getSession()->getFlashBag()->add('error',"Data has been reset successfully");
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    /**
     * Creates a form to delete a GlobalOption entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('globaloption_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * Edits an existing GlobalOption entity.
     *
     */
    public function modifyUpdateAction(Request $request, $id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GlobalOption entity.');
        }

        $editForm = $this->createModifyForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entity->upload();
            $em->flush();
            //$this->em->getRepository(\App\Content\Entity\HomePage::class)->globalOptionHome($user);
            //$this->em->getRepository(\App\Content\Entity\ContactPage::class)->globalOptionContact($user);
            $this->em->getRepository(\App\Tool\Entity\SiteSetting::class)->updateSettingMenu($entity);
            $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->systemConfigUpdate($entity);
            $this->requestStack->getSession()->getFlashBag()->add('success',"Data has been updated successfully");

            /*$referer = $request->headers->get('referer');
            return new RedirectResponse($referer);*/
            return $this->redirect($this->generateUrl('globaloption_edit', array('id' => $entity->getId())));


        }
        return $this->render('Tool/GlobalOption/new.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));


    }

    /**
     * Creates a form to edit a GlobalOption entity.
     *
     * @param GlobalOption $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createModifyForm(GlobalOption $entity)
    {
        $location = $this->em->getRepository(\App\Location\Entity\Location::class);
        $form = $this->createForm(GlobalOptionModifyType::class, $entity, array('action' => $this->generateUrl('globaloption_modify_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'novalidate' => 'novalidate',
            ), 'location' => $location));

        return $form;

    }


    /**
     * Displays a form to edit an existing GlobalOption entity.
     *
     */
    public function modifyAction()
    {
        $em = $this->em;
        $entity = $this->getUser()->getGlobalOption();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find GlobalOption entity.');
        }

        $this->toolManager->createDirectory($entity->getId());
        $this->em->getRepository(\App\Tool\Entity\SiteSetting::class)->globalOptionSetting($entity);
        $editForm = $this->createModifyForm($entity);

        $subSyndicates = $this->getSubSyndicateUnderVendor($entity);

        return $this->render('Tool/GlobalOption/new.html.twig', array(
            'entity'        => $entity,
            'edit_form'     => $editForm->createView(),
            'subSyndicates' => $subSyndicates
        ));
    }

    public function getSubSyndicateUnderVendor($entity)
    {
        $em = $this->em;
        if($entity->getSyndicate()){
            $parentId       = $entity->getSyndicate()->getId();
            $syndicates     = $em->getRepository(\App\Tool\Entity\Syndicate::class)->findBy(array('status' => 1,'parent' => $parentId),array('name' => 'asc'));
            return $subSyndicates  = $this->em->getRepository(\App\Tool\Entity\Syndicate::class)->getSelectedSubSyndicates($syndicates,$entity);
        }

    }

    public function webmailAction()
    {
        return $this->render('Tool/GlobalOption/webmail.html.twig', array(
            'entity'      => $this->getUser()->getGlobalOption(),

        ));
    }

    public function vendorSearchAction()
    {
        echo 'Test';
    }
}
