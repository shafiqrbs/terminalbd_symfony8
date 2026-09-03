<?php

namespace App\User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
class DefaultController extends AbstractController
{
    public function indexAction()
    {
        return $this->render('User/Default/index.html.twig', []);
    }

    public function landingAction()
    {
        $user = $this->getUser();
        if(empty($user)){
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $globalOption = $user->getGlobalOption();
        /* @var $globalOption GlobalOption */

        $this->get('settong.toolManageRepo')->createMasterDirectory($globalOption->getId());

        if($globalOption->getStatus() == 2 or $globalOption->getStatus() == 3 ) {

            $this->get('security.context')->setToken(null);
            $this->get('request')->getSession()->invalidate();
            $this->get('session')->getFlashBag()->add('notice', "Your account has been temporary suspended. Please contact administrator for further any query");
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }elseif($globalOption->getStatus() == 4 ){
            $this->get('security.context')->setToken(null);
            $this->get('request')->getSession()->invalidate();
            $this->get('session')->getFlashBag()->add(
                'error',"Your account has been deleted. Please contact administrator for further any query"
            );
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }else{
            $enable = $globalOption->getStatus();
        }

        $apps = [];
        if (!empty($globalOption ->getSiteSetting())) {

        	$modules = $globalOption->getSiteSetting()->getAppModules();

            /* @var AppModule $mod */

            foreach ($modules as $mod) {
                if (!empty($mod->getModuleClass())) {
                    $apps[] = $mod->getSlug();
                }
            }
        }

        $mainApp = !empty($globalOption->getMainApp()) ? $globalOption->getMainApp()->getSlug() : "";

	    if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirect($this->generateUrl('tools_domain'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_DOMAIN') && $enable != 1) {
            return $this->redirect($this->generateUrl('bindu_build'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_RESTAURANT') && $enable == 1 and $mainApp == 'restaurant') {
            return $this->redirect($this->generateUrl('restaurant_homepage'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_DPS') && $enable == 1 &&  $mainApp == 'dps') {
            return $this->redirect($this->generateUrl('dps_homepage'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_HOSPITAL') && $enable == 1 &&  $mainApp == 'hms' ) {
            return $this->redirect($this->generateUrl('hospital_homepage'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_MEDICINE') && $enable == 1 && $mainApp == 'miss' ) {
            return $this->redirect($this->generateUrl('medicine_homepage'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_BUSINESS') && $enable == 1 && $mainApp == 'business' ) {
            return $this->redirect($this->generateUrl('business_homepage'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_INVENTORY') && $enable == 1 && $mainApp == 'inventory') {
        	return $this->redirect($this->generateUrl('inventory_homepage'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_WEBSITE') && $enable == 1 && $mainApp == 'website') {
            return $this->redirect($this->generateUrl('website'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_WEBSITE') && $enable == 1 && $mainApp == 'accounting') {
            return $this->redirect($this->generateUrl('account_transaction_cash_summary'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_DOMAIN') && $enable == 1) {
	        return $this->redirect($this->generateUrl('domain'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_AGENT')) {
              return $this->redirect($this->generateUrl('agentclient'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_CUSTOMER')) {
              return $this->redirect($this->generateUrl('customer'));
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_ACCOUNTING') && $enable == 1 ) {
            return $this->redirect($this->generateUrl('account_transaction_cash_overview'));
        }elseif (!empty($user) && $enable == 2 ) {
            return $this->redirect($this->generateUrl('domain_pendig'));
        }elseif (!empty($user) && $enable == 3 ) {
            return $this->redirect($this->generateUrl('domain_suspended'));
        }else{
            return $this->redirect($this->generateUrl('bindu_homepage'));
        }

    }

    public function lockAction(){
        return $this->render('User/Default/lock.html.twig', []);
    }

    public function adminAction()
    {
        $user = $this->getUser();
        $em = $this->get('doctrine.orm.entity_manager');
        return $this->render('User/Default/admin.html.twig', [
            'user' => $user
        ]);

    }

    public function userAction()
    {
        $user = $this->getUser();
        $em = $this->get('doctrine.orm.entity_manager');

        return $this->render('User/Default/admin.html.twig', [
            'user' => $user
        ]);

    }
    public function domainAction()
    {

        /* @var GlobalOption $globalOption */
        $globalOption = $this->getUser()->getGlobalOption();
        $modules = $globalOption->getSiteSetting()->getAppModules();
        $apps = [];
        if (!empty($globalOption ->getSiteSetting()) and !empty($modules)) {
            /* @var AppModule $mod */
            foreach ($modules as $mod) {
                if (!empty($mod->getModuleClass())) {
                    $apps[] = $mod->getSlug();
                }
            }
        }
        return $this->render('User/Default/domain.html.twig', [
            'globalOption' => $globalOption,
             'apps' => $apps
        ]);
    }

    public function websiteAction()
    {
        /* @var GlobalOption $globalOption */
        $globalOption = $this->getUser()->getGlobalOption();
        $modules = $globalOption->getSiteSetting()->getAppModules();
        $apps = [];
        if (!empty($globalOption ->getSiteSetting()) and !empty($modules)) {
            /* @var AppModule $mod */
            foreach ($modules as $mod) {
                if (!empty($mod->getModuleClass())) {
                    $apps[] = $mod->getSlug();
                }
            }
        }
        return $this->render('User/Default/domain.html.twig', [
            'globalOption' => $globalOption,
            'apps' => $apps
        ]);

    }

    public function hospitalAction()
    {
        /* @var GlobalOption $globalOption */
        $globalOption = $this->getUser()->getGlobalOption();
        $modules = $globalOption->getSiteSetting()->getAppModules();
        $apps = [];
        if (!empty($globalOption ->getSiteSetting()) and !empty($modules)) {
            /* @var AppModule $mod */
            foreach ($modules as $mod) {
                if (!empty($mod->getModuleClass())) {
                    $apps[] = $mod->getSlug();
                }
            }
        }
        return $this->render('User/Default/domain.html.twig', [
            'globalOption' => $globalOption,
            'apps' => $apps
        ]);

    }


    public function pendingAction()
    {
        $user = $this->getUser();
        return $this->render('User/Default/pending.html.twig', [
            'user' => $user,
        ]);
    }

    public function suspendedAction()
    {
        $user = $this->getUser();
        return $this->render('User/Default/lock.html.twig', [
            'user' => $user,
        ]);
    }

    public function vendorAction()
    {

        $user = $this->getUser();
        $em = $this->get('doctrine.orm.entity_manager');
        return $this->render('User/Default/domain.html.twig', [
            'user' => $user
        ]);
    }
    public function tutorAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        return $this->render('User/Default/index.html.twig', []);
    }

    public function generalAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        return $this->render('User/Default/index.html.twig', []);
    }


}