<?php

namespace App\Tool\Controller;

use Symfony\Component\HttpKernel\KernelInterface;

use App\User\Manager\UserManager;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Tool\Event\DomainNotification;
use App\Tool\Event\PasswordChangeDomainSmsEvent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use App\Tool\Entity\GlobalOption;
use App\Tool\Entity\SmsSender;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;


/**
 * ApplicationPricing controller.
 *
 */
class DomainController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserManager $userManager,
        private readonly KernelInterface $kernel,
    ) {
    }



    public function generateDomainPathAction()
    {

	    set_time_limit(0);
    	$em = $this->em;
        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->findByDomain();

        $domains = [
            [
                'resource' => '@FrontendBundle/Resources/config/routing/ecommercesubdomain.yml',
                'domain' => 'www.tlsbd.org',
                'subdomain' => 'tlsbd'
            ]
        ];

        $resourceWebsite = '@FrontendBundle/Resources/config/routing/ecommercesubdomain.yml';
	    $resourceEcommerce = '@FrontendBundle/Resources/config/routing/ecommercesubdomain.yml';
        $routes = [];

        /* @var $data GlobalOption */
        foreach ($entities as $data){

        	if($data ->getDomainType() == 'ecommerce'){
		        $resource = $resourceEcommerce;
	        }else{
		        $resource = $resourceWebsite;
	        }
	        $routes['_www_domain_app_' . strtolower(str_replace(['.','-'], '_', $data->getDomain()))] = [
                'resource' => $resource ,
                'host' => '{domain_name}',
                'name_prefix' => $data->getSubDomain() . "_",
                'defaults' => [
                    'subdomain' => $data->getSubDomain(),
                    'domain_name' => 'www.' . $data->getDomain()
                ],
                'requirements' => [
                    'domain_name' => sprintf('www.%s|%s', $data->getDomain(), $data->getDomain())
                ]
            ];
           /* $routes['_domain_app_' . strtolower(str_replace('.', '_', $data->getDomain()))] = array(
                'resource' => $resource ,
                'host' => $data->getDomain(),
                'name_prefix' => $data->getSubDomain() . "_",
                'defaults' => array(
                    'subdomain' => $data->getSubDomain()
                )
            );*/

        }

        $routesString = Yaml::dump($routes);

        file_put_contents(realpath(WEB_PATH . "/../app/config/dynamic/sites.yml"), $routesString);

        return $this->redirect($this->generateUrl('tools_domain'));

    }


    public function paginate($entities)
    {
	    $paginator = $this->paginator;
	    $pagination = $paginator->paginate(
		    $entities,
		    $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
		    25  /*limit per page*/
	    );
	    $pagination->setTemplate('Tool/Widget/pagination.html.twig');
	    return $pagination;
    }

    public function databaseDumpAction(){

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $DBUSER="root";
        $DBPASSWD="*rbs*terminalbd#";
        $DATABASE="poskeeepr";

        $filename = "terminaldb-" . date("d-m-Y") . ".sql.gz";
        $mime = "application/x-gzip";

        header( "Content-Type: " . $mime );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        $cmd = "mysqldump -u $DBUSER --password=$DBPASSWD $DATABASE | gzip --best";
        passthru( $cmd );
        exit(0);
        
    }


    /**
     * Lists all GlobalOption entities.
     *
     */
    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
      //  $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->getList($data);
        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->getList($data);
        $entities = $this->paginate($entities);
        $apps = $this->em->getRepository(\App\Tool\Entity\AppModule::class)->findBy(['status'=>1],['name'=>"ASC"]);
        //$this->statusDeleteAction();
       // exit;
        return $this->render('Tool/Domain/index.html.twig', [
            'entities' => $entities,
            'apps' => $apps,
            'searchForm' => $data,
        ]);
    }

     /**
     * Lists all GlobalOption entities.
     *
     */
    public function clientAction()
    {
        $em = $this->em;
        $user = $this->getUser();
        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->findBy(['agent' => $user]);
        $entities = $this->paginate($entities);
        return $this->render('Tool/Domain/client.html.twig', [
            'entities' => $entities,
        ]);
    }

    public function optionStatusAction()
    {
        $items = [];
        $items[]=['value' =>1,'text'=> 'Active'];
        $items[]=['value' =>2,'text'=> 'Hold'];
        $items[]=['value' =>3,'text'=> 'Suspended'];
        $items[]=['value' =>4,'text'=> 'Deleted'];
        return new JsonResponse($items);
   }

    public function optionStatusUpdateAction(Request $request)
    {
        $data = $request->request->all();
        $em = $this->em;
        $entity = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->find($data['pk']);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }
        $process = 'set'.$data['name'];
        $entity->$process($data['value']);
        $em->flush();

        if($entity->getStatus() != 1){
            $dispatcher = $this->eventDispatcher;
            $dispatcher->dispatch(new DomainNotification($entity), 'setting_tool.post.domain_notification');
        }
        exit;
    }

    public function resetDomainPasswordAction(GlobalOption $option)
    {
        $entity = $this->em->getRepository(\App\User\Entity\User::class)->findOneBy(['globalOption'=> $option,'domainOwner' => 1]);
        if(!empty($entity)){
            //$a = mt_rand(1000,9999);
            $a = '*148148#';
            $entity->setPlainPassword($a);
            $this->userManager->updateUser($entity);
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Change password successfully"
            );
        }
        return $this->redirect($this->generateUrl('tools_domain'));

    }

    public function resetManualDomainPasswordAction(Request $request, GlobalOption $option)
    {
        $entity = $this->em->getRepository(\App\User\Entity\User::class)->findOneBy(['globalOption'=> $option,'domainOwner'=>1]);
        if(!empty($entity)){
            $a = $request->request->get('password');
            $entity->setPlainPassword($a);
            $this->userManager->updateUser($entity);
            $dispatcher = $this->eventDispatcher;
            $dispatcher->dispatch(new PasswordChangeDomainSmsEvent($option,$entity->getUsername(),$a), 'setting_tool.post.change_domain_password');
        }
        exit;
    }

    public function resetSystemDataAction(GlobalOption $option)
    {

        set_time_limit(0);

        if($option->getAccountingConfig()){
            $this->em->getRepository(\App\Accounting\Entity\AccountingConfig::class)->accountingReset($option);
        }

        if($option->getInventoryConfig()) {
           $this->em->getRepository(\App\Inventory\Entity\InventoryConfig::class)->inventoryReset($option);
        }

        if($option->getHospitalConfig()) {
            $this->em->getRepository(\App\Hospital\Entity\HospitalConfig::class)->hospitalReset($option);
        }
        if($option->getRestaurantConfig()) {
            $this->em->getRepository(\App\Restaurant\Entity\RestaurantConfig::class)->reset($option);
        }
        if($option->getMedicineConfig()) {
            $this->em->getRepository(\App\Medicine\Entity\MedicineConfig::class)->medicineReset($option);
        }
        if($option->getBusinessConfig()) {
            $this->em->getRepository(\App\Business\Entity\BusinessConfig::class)->businessReset($option);
        }
        $dir = WEB_PATH . "/uploads/domain/" . $option->getId() . "/inventory";
        $a = new Filesystem();
        $a->remove($dir);
        $a->mkdir($dir);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Successfully reset data"
        );
        return new Response('success');


    }

    public function domainDeleteAction(GlobalOption $option)
    {
        $em = $this->em;
        set_time_limit(0);
        $dir = WEB_PATH . "/uploads/domain/" . $option->getId();
        $a = new Filesystem();
        $a->remove($dir);
        $a->mkdir($dir);
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->posReset($option);
        if(!empty($option->getAccountingConfig()) and $option->getAccountingConfig()){
            $this->em->getRepository(\App\Accounting\Entity\AccountingConfig::class)->accountingReset($option);
        }

        if(!empty($option->getInventoryConfig()) and $option->getInventoryConfig()) {
            $this->em->getRepository(\App\Inventory\Entity\InventoryConfig::class)->inventoryReset($option);
        }
        if(!empty($option->getDpsConfig()) and $option->getDmsConfig()) {
            $this->em->getRepository(\App\DoctorPrescription\Entity\DpsConfig::class)->dpsReset($option);
        }

        if(!empty($option->getHospitalConfig()) and $option->getHospitalConfig()) {
            $this->em->getRepository(\App\Hospital\Entity\HospitalConfig::class)->hospitalDelete($option);
        }


        if(!empty($option->getMedicineConfig()) and $option->getMedicineConfig()) {
            $this->em->getRepository(\App\Medicine\Entity\MedicineConfig::class)->medicineDelete($option);
        }
        if(!empty($option->getBusinessConfig()) and $option->getBusinessConfig()) {
            $this->em->getRepository(\App\Business\Entity\BusinessConfig::class)->businessDelete($option);
        }
         if(!empty($option->getRestaurantConfig()) and $option->getRestaurantConfig()) {
            $this->em->getRepository(\App\Restaurant\Entity\RestaurantConfig::class)->delete($option);
        }

        $em->remove($option);
        $em->flush();

        /* Menu, Application Setting, website, module, apps , user*/

        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Successfully reset data"
        );
        return $this->redirect($this->generateUrl('tools_domain'));
    }

    public function domainDeletePurchaseSalesAction(GlobalOption $option)
    {
        set_time_limit(0);
        $this->em->getRepository(GlobalOption::class)->domainDeletePurchaseSales($option);
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Successfully reset data"
        );
        return $this->redirect($this->generateUrl('tools_domain'));
    }

    public function statusDeleteAction()
    {
        $em = $this->em;
        set_time_limit(0);
        $data = $_REQUEST;
        $entities = $em->getRepository(\App\Tool\Entity\GlobalOption::class)->getRemoveList($data);
        $entities = $this->paginate($entities);
        foreach ($entities as $option):

      /*
        $dir = WEB_PATH . "/uploads/domain/" . $option->getId();
        $a = new Filesystem();
        $a->remove($dir);
        $a->mkdir($dir);
      */
        $this->em->getRepository(\App\Pos\Entity\Pos::class)->posReset($option);
        if(!empty($option->getAccountingConfig()) and $option->getAccountingConfig()){
            $this->em->getRepository(\App\Accounting\Entity\AccountingConfig::class)->accountingReset($option);
        }

        if(!empty($option->getInventoryConfig()) and $option->getInventoryConfig()) {
            $this->em->getRepository(\App\Inventory\Entity\InventoryConfig::class)->inventoryDelete($option);
        }
        if(!empty($option->getDpsConfig()) and $option->getDmsConfig()) {
            $this->em->getRepository(\App\DoctorPrescription\Entity\DpsConfig::class)->dpsReset($option);
        }

        if(!empty($option->getHospitalConfig()) and $option->getHospitalConfig()) {
            $this->em->getRepository(\App\Hospital\Entity\HospitalConfig::class)->hospitalDelete($option);
        }

        if(!empty($option->getMedicineConfig()) and $option->getMedicineConfig()) {
            $this->em->getRepository(\App\Medicine\Entity\MedicineConfig::class)->medicineDelete($option);
        }
        if(!empty($option->getBusinessConfig()) and $option->getBusinessConfig()) {
            $this->em->getRepository(\App\Business\Entity\BusinessConfig::class)->businessDelete($option);
        }
        if(!empty($option->getRestaurantConfig()) and $option->getRestaurantConfig()) {
            $this->em->getRepository(\App\Restaurant\Entity\RestaurantConfig::class)->delete($option);
        }
        $em->remove($option);
        $em->flush();
        endforeach;

        /* Menu, Application Setting, website, module, apps , user*/

        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Successfully reset data"
        );
        return $this->redirect($this->generateUrl('tools_domain'));
    }


    public function androidDataCleanAction()
    {
        $em = $this->em;
        $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->androidDataClean();
        $this->em->getRepository(SmsSender::class)->smsHistoryDelete();
        $this->requestStack->getSession()->getFlashBag()->add(
            'success',"Successfully reset data"
        );
        return $this->redirect($this->generateUrl('tools_domain'));
    }

    public function cacheClearAction()
    {

        exec("rm -rf app/cache/*");
        exec("rm -rf app/logs/*");
        $kernel = $this->kernel;
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $options = ['command' => 'cache:clear',"--env" => 'prod', '--no-warmup' => true];
        $application->run(new ArrayInput($options));
        return $this->redirect($this->generateUrl('tools_domain'));

    }

}
