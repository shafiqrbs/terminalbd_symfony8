<?php

namespace Bindu\BinduBundle\Controller;

use Appstore\Bundle\AccountingBundle\Entity\AccountBank;
use Appstore\Bundle\AccountingBundle\Entity\AccountMobileBank;
use Appstore\Bundle\MedicineBundle\Entity\MedicineStock;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Gregwar\Image\Image;
use Setting\Bundle\AppearanceBundle\Entity\TemplateCustomize;
use Setting\Bundle\ToolBundle\Entity\GlobalOption;
use Setting\Bundle\ToolBundle\Entity\TransactionMethod;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Terminalbd\ReportBundle\Entity\AppsApi;

class ApiPoskeeperController extends Controller
{

    public function checkApiValidation($request)
    {
        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        $unique = $request->headers->get('X-API-SECRET');
        $setup = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->findOneBy(array('uniqueCode' => $unique,'status'=> 1));
        if (!empty($setup) and $request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            return $setup;
        }
        return "invalid";
    }

    public function checkApiLoginValidation($request)
    {
        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        if ($request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            return "success";
        }
        return "invalid";
    }

    public function appsAction(Request $request)
    {

        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        if ($request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            $data =$this->getDoctrine()->getRepository('SettingToolBundle:AppModule')->getAndroidAppModules();
        }else{
            return new Response('Unauthorized access.', 401);
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    public function setupAction(Request $request)
    {

        $formData = $request->request->all();
        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        $uniqueCode = $formData['uniqueCode'];
        $mobile = $formData['mobile'];
        $deviceId = $formData['deviceId'];
        $data = array();
        $entity = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->findOneBy(array('uniqueCode' => $uniqueCode,'mobile' => $mobile,'status'=>1));
        if (empty($entity) and $request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            return new Response('Unauthorized access.', 401);
        }else{

            /* @var $entity GlobalOption */

            $device = $this->getDoctrine()->getRepository('SettingToolBundle:AndroidDeviceSetup')->insert($entity,$deviceId);
            if($device) {
                $address = '';
                $vatRegNo = '';
                $vatPercentage = '';
                $vatEnable = '';
                $printFooter = "";
                if ($entity->getMainApp()->getSlug() == "miss") {
                    $address = $entity->getMedicineConfig()->getAddress();
                    $vatPercentage = $entity->getMedicineConfig()->getVatPercentage();
                    $vatRegNo = $entity->getMedicineConfig()->getVatRegNo();
                    $vatEnable = $entity->getMedicineConfig()->isVatEnable();
                    $footerMessage = $entity->getMedicineConfig()->getPrintFooterText();
                    if($footerMessage){
                        $printFooter = $footerMessage;
                    }else{
                        $printFooter = "Thanks For Visiting our pharmacy";
                    }
                } elseif ($entity->getMainApp()->getSlug() == "business") {
                    $address = $entity->getBusinessConfig()->getAddress();
                    $vatPercentage = $entity->getBusinessConfig()->getVatPercentage();
                    $vatRegNo = $entity->getBusinessConfig()->getVatRegNo();
                    $vatEnable = $entity->getBusinessConfig()->getVatEnable();
                    $footerMessage = $entity->getBusinessConfig()->getPrintFooterText();
                    if($footerMessage){
                        $printFooter = $footerMessage;
                    }else{
                        $printFooter = "Thanks For Visiting our business store";
                    }
                } elseif ($entity->getMainApp()->getSlug() == "restaurant") {
                    $address = $entity->getRestaurantConfig()->getAddress();
                    $vatPercentage = $entity->getRestaurantConfig()->getVatPercentage();
                    $vatRegNo = $entity->getRestaurantConfig()->getVatRegNo();
                    $vatEnable = $entity->getRestaurantConfig()->getVatEnable();
                    $footerMessage = $entity->getRestaurantConfig()->getPrintFooterText();
                    if($footerMessage){
                        $printFooter = $footerMessage;
                    }else{
                        $printFooter = "Thanks For Visiting our restaurant";
                    }
                } elseif ($entity->getMainApp()->getSlug() == "inventory") {
                    $address = $entity->getInventoryConfig()->getAddress();
                    $vatPercentage = $entity->getInventoryConfig()->getVatPercentage();
                    $vatRegNo = $entity->getInventoryConfig()->getVatRegNo();
                    $vatEnable = $entity->getInventoryConfig()->getVatEnable();
                    $footerMessage = $entity->getInventoryConfig()->getPrintFooterText();
                    if($footerMessage){
                        $printFooter = $footerMessage;
                    }else{
                        $printFooter = "Thanks For Visiting our shop";
                    }
                }
                $mobile = empty($entity->getHotline()) ? $entity->getMobile() : $entity->getHotline();
                $data = array(
                    'setupId' => $entity->getId(),
                    'deviceId' => $device,
                    'uniqueCode' => $entity->getUniqueCode(),
                    'name' => $entity->getName(),
                    'mobile' => $mobile,
                    'email' => $entity->getEmail(),
                    'locationId' => $entity->getLocation()->getId(),
                    'address' => $address,
                    'locationName' => $printFooter,
                    'main_app' => $entity->getMainApp()->getId(),
                    'main_app_name' => $entity->getMainApp()->getSlug(),
                    'appsManual' => $entity->getMainApp()->getApplicationManual(),
                    'website' => $entity->getDomain(),
                    'vatRegNo' => $vatRegNo,
                    'vatPercentage' => $vatPercentage,
                    'vatEnable' => $vatEnable,
                );
            }
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    function hex6ToHex8($hex6) {
        return str_replace("#","0xFF",$hex6);
    }

    function random_color_code() {
        $red = str_pad(dechex(rand(0, 255)), 2, '0', STR_PAD_LEFT);
        $green = str_pad(dechex(rand(0, 255)), 2, '0', STR_PAD_LEFT);
        $blue = str_pad(dechex(rand(0, 255)), 2, '0', STR_PAD_LEFT);
        return '#' . $red . $green . $blue;
    }

    public function stringNullChecker($var){
        if (is_null($var)) {
            return "";
        } else {
            return (string) $var;
        }
    }

    public function numberNullChecker($var){
        if (is_null($var)) {
            return 0;
        } else {
            return (float) $var;
        }
    }

    public function splashAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $formData = $request->request->all();
        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        $uniqueCode = $formData['uniqueCode'];
        $mobile = $formData['mobile'];
        $deviceId = $formData['deviceId'];
        $setup = array();
        $entity = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->findOneBy(array('uniqueCode' => $uniqueCode,'mobile' => $mobile,'status'=>1));
        if (empty($entity) and $request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            return new Response('Unauthorized access.', 401);
        }else {

            $device = $this->getDoctrine()->getRepository('SettingToolBundle:AndroidDeviceSetup')->insert($entity, $deviceId);
            if ($device) {
                $setup = $this->getDoctrine()->getRepository(AppsApi::class)->getApiSetup($entity,$device);
            }
            $users = $this->getDoctrine()->getRepository(AppsApi::class)->getApiUsers($entity);
            $customers = $this->getDoctrine()->getRepository(AppsApi::class)->getApiCustomerOutstanding($entity);
            $vendors = $this->getDoctrine()->getRepository(AppsApi::class)->apiApiVendorOutstanding($entity);
            $stocks = $this->getDoctrine()->getRepository(AppsApi::class)->getApiStock($entity);
            $categories = $this->getDoctrine()->getRepository(AppsApi::class)->getApiParticular($entity,'category');
            $brands = $this->getDoctrine()->getRepository(AppsApi::class)->getApiParticular($entity,'brand');
            $methods = $this->getDoctrine()->getRepository(AppsApi::class)->getApiMethodTransaction($entity);
            $expenseCategories = $this->getDoctrine()->getRepository(AppsApi::class)->getApiExpenseCategory($entity->getId());
            $units = $this->getDoctrine()->getRepository(AppsApi::class)->getApiProductUnit();
        }
        $jsonData = array(
            'brands'                => $brands,
            'categories'            => $categories,
            'customers'             => $customers,
            'expense_category'      => $expenseCategories,
            'setup'                 => $setup,
            'stocks'                => $stocks,
            'transaction_methods'   => $methods,
            'users'                 => $users,
            'units'                 => $units,
            'vendors'               => $vendors,
        );
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($jsonData));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

    public function masterdataAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $setup = $this->getDoctrine()->getRepository(AppsApi::class)->getApiSetup($entity,'');
            $users = $this->getDoctrine()->getRepository(AppsApi::class)->getApiUsers($entity);
            $customers = $this->getDoctrine()->getRepository(AppsApi::class)->getApiCustomerOutstanding($entity);
            $vendors = $this->getDoctrine()->getRepository(AppsApi::class)->apiApiVendorOutstanding($entity);
            $categories = $this->getDoctrine()->getRepository(AppsApi::class)->getApiParticular($entity,'category');
            $brands = $this->getDoctrine()->getRepository(AppsApi::class)->getApiParticular($entity,'brand');
            $methods = $this->getDoctrine()->getRepository(AppsApi::class)->getApiMethodTransaction($entity);
            $expenseCategories = $this->getDoctrine()->getRepository(AppsApi::class)->getApiExpenseCategory($entity->getId());
            $units = $this->getDoctrine()->getRepository(AppsApi::class)->getApiProductUnit();
        }
        $jsonData = array(
            'brands'                => $brands,
            'categories'            => $categories,
            'customers'             => $customers,
            'expense_category'      => $expenseCategories,
            'setup'                 => $setup,
            'transaction_methods'   => $methods,
            'users'                 => $users,
            'units'                 => $units,
            'vendors'               => $vendors,
        );
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($jsonData));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

    public function resizeFilter($pathToImage, $width = 400, $height = 400)
    {
        $path = '/' . Image::open(__DIR__.'/../../../../../web/' . $pathToImage)->cropResize($width, $height, 'transparent', 'top', 'left')->guess();
        return "http://{$_SERVER['HTTP_HOST']}/{$pathToImage}";
    }

    public function dashboardAction(Request $request)
    {

        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');
            $datetime = new \DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
            $data['device'] = $deviceId;
            $purchaseCashOverview = $this->getDoctrine()->getRepository('MedicineBundle:MedicinePurchase')->androidDevicePurchaseOverview($entity,$data);
            $salesCashOverview = $this->getDoctrine()->getRepository('MedicineBundle:MedicineSales')->androidDeviceSalesOverview($entity,$data);
            $expenditureOverview = $this->getDoctrine()->getRepository('AccountingBundle:Expenditure')->androidDeviceExpenditureOverview($entity,$data);
            $data = array(
                'globalOption'              => $entity->getId(),
                'expenditureOverview'       => $expenditureOverview ,
                'totalSales'                => $salesCashOverview['total'] ,
                'salesReceive'              => $salesCashOverview['salesReceive'] ,
                'salesVoucher'              => $salesCashOverview['voucher'] ,
                'purchaseTotal'             => $purchaseCashOverview['total'] ,
                'purchasePayment'           => $purchaseCashOverview['payment'] ,
            );

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function loginAction(Request $request)
    {

        $formData = $request->request->all();
        $mobile = $formData['mobile'];
        $data = array();
        if( $this->checkApiLoginValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else {
            $user = $this->getDoctrine()->getRepository('UserBundle:User')->getAndroidOTP($mobile);
            if($user){
                $login = $this->getDoctrine()->getRepository("UserBundle:User")->find($user);
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->dispatch('setting_tool.post.change_password', new \Setting\Bundle\ToolBundle\Event\PasswordChangeSmsEvent($login, $login->getAppPassword()));
                $data = array('status'=>'valid','otp' => $login->getAppPassword(),'apiSecret'=> $login->getGlobalOption()->getUniqueCode());
            }else{
                $data = array('status'=>'in-valid','otp' => '');
            }
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    public function storeBuildAction(Request $request)
    {

        $formData = $request->request->all();
        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        $deviceId = $formData['deviceId'];
        $mobile = $formData['mobile'];
        $entity = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->findOneBy(array('mobile' => $mobile));
        $userExist = $this->getDoctrine()->getRepository('UserBundle:User')->checkExistingUser($mobile);
        if (empty($formData) and $request->headers->get('X-API-KEY') != $key and $request->headers->get('X-API-VALUE') != $value) {
            return new Response('Unauthorized access.', 401);
        }elseif (!empty($entity) and !empty($userExist)  and  $request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            $data = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->getSetupInformation($entity);
            $device = $this->getDoctrine()->getRepository('SettingToolBundle:AndroidDeviceSetup')->insert($entity,$deviceId);
            /* @var $entity GlobalOption */
            if($device) {
                $mobile = empty($entity->getHotline()) ? $entity->getMobile() : $entity->getHotline();$address = $entity->getMedicineConfig()->getAddress();
                $vatPercentage = $entity->getMedicineConfig()->getVatPercentage();
                $vatRegNo = $entity->getVatPercent();
                $vatEnable = $entity->isVatEnable();
                $footerMessage = $entity->getPrintMessage();
                if($footerMessage){
                    $printFooter = $footerMessage;
                }else{
                    $printFooter = "Thanks For Visiting our store";
                }
                $data = array(
                    'setupId' => $entity->getId(),
                    'deviceId' => $device,
                    'uniqueCode' => $entity->getUniqueCode(),
                    'name' => $entity->getName(),
                    'mobile' => $mobile,
                    'email' => $entity->getEmail(),
                    'symbol' => $entity->getCurrency() ? $entity->getCurrency()->getSymbol() : '',
                    'country' => $entity->getCurrency() ? $entity->getCountry()->getName() : '',
                    'currency' => $entity->getCurrency() ? $entity->getCurrency()->getCurrency():'',
                    'address' => $entity->getAddress(),
                    'posPrint' => $printFooter,
                    'main_app' => $entity->getMainApp()->getId(),
                    'main_app_name' => $entity->getMainApp()->getSlug(),
                    'appsManual' => $entity->getMainApp()->getApplicationManual(),
                    'website' => $entity->getDomain(),
                    'vatRegNo' => $vatRegNo,
                    'vatPercentage' => $vatPercentage,
                    'vatEnable' => $vatEnable,
                );
            }
        }elseif ($formData and $request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            $entity = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->insertSetupInformation($formData);
            $this->getDoctrine()->getRepository('UserBundle:User')->androidUserCreate($entity,$formData);
            $device = $this->getDoctrine()->getRepository('SettingToolBundle:AndroidDeviceSetup')->insert($entity,$deviceId);
            if($device) {
                $mobile = empty($entity->getHotline()) ? $entity->getMobile() : $entity->getHotline();$address = $entity->getMedicineConfig()->getAddress();
                $vatPercentage = $entity->getMedicineConfig()->getVatPercentage();
                $vatRegNo = $entity->getVatPercent();
                $vatEnable = $entity->isVatEnable();
                $footerMessage = $entity->getPrintMessage();
                if($footerMessage){
                    $printFooter = $footerMessage;
                }else{
                    $printFooter = "Thanks For Visiting our store";
                }
                $data = array(
                    'setupId' => $entity->getId(),
                    'deviceId' => $device,
                    'uniqueCode' => $entity->getUniqueCode(),
                    'name' => $entity->getName(),
                    'mobile' => $mobile,
                    'email' => $entity->getEmail(),
                    'symbol' => $entity->getCurrency() ? $entity->getCurrency()->getSymbol() : '',
                    'country' => $entity->getCurrency() ? $entity->getCountry()->getName() : '',
                    'currency' => $entity->getCurrency() ? $entity->getCurrency()->getCurrency():'',
                    'address' => $entity->getAddress(),
                    'posPrint' => $printFooter,
                    'main_app' => $entity->getMainApp()->getId(),
                    'main_app_name' => $entity->getMainApp()->getSlug(),
                    'appsManual' => $entity->getMainApp()->getApplicationManual(),
                    'website' => $entity->getDomain(),
                    'vatRegNo' => $vatRegNo,
                    'vatPercentage' => $vatPercentage,
                    'vatEnable' => $vatEnable,
                );
            }
        }else{
            return new Response('Unauthorized access.', 401);
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    public function multiUserAction(Request $request)
    {

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $formData = $request->request->all();
            $entity = $this->checkApiValidation($request);
            $setup = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->updateSetupInformation($entity,$formData);
             $data = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->getSetupInformation($setup);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function createUserAction(Request $request)
    {

        $formData = $request->request->all();
        $username = $formData['username'];
        $userExist = $this->getDoctrine()->getRepository('UserBundle:User')->checkExistingUser($username);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }elseif(empty($userExist)){

            $entity = $this->checkApiValidation($request);
            $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->updateSetupInformation($entity,$formData);
            $userId = $this->getDoctrine()->getRepository('UserBundle:User')->androidUserCreate($entity,$formData);
            $user = $this->getDoctrine()->getRepository('UserBundle:User')->find($userId);
            $data = array(
                'username' => $user->getUsername(),
                'name' => $user->getName(),
                'status' => 'success'
            );
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }else{
            return new Response('Unauthorized access.', 401);
        }
    }

    public function systemUsersAction(Request $request)
    {

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('UserBundle:User')->getUsers($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function checkDuplicateUserAction(Request $request)
    {
        $username = $request->request->get('username');
        $user = $this->getDoctrine()->getManager()->getRepository("UserBundle:User")
            ->checkExistingUser($username);
        if ($user == false) {
            $data = array(
                'status' => 'valid'
            );
        }else {
            $data = array(
                'status' => 'invalid'
            );
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;


    }

    public function editUserAction(Request $request)
    {
        $username = $request->request->get('user_id');
        $user = $this->getDoctrine()->getManager()->getRepository("UserBundle:User")
            ->findOneBy(array('id' => $username));
        $data = array(
            'user_id' => $username,
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'role' => $user->getAndroidRole()
        );
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

    public function updateUserAction(Request $request)
    {

        $formData = $request->request->all();
        $username = $formData['user_id'];
        $userExist = $this->getDoctrine()->getRepository('UserBundle:User')->find($username);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }elseif($userExist){

            $this->getDoctrine()->getRepository('UserBundle:User')->androidUserUpdate($userExist,$formData);
            $data = array('status' => 'success');
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;

        }else{

            return new Response('Unauthorized access.', 401);
        }
    }

    public function medicineGlobalAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $_REQUEST;
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineBrand')->getApiDims($data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function StockItemAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $stocks = $this->getDoctrine()->getRepository(AppsApi::class)->getApiStock($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($stocks));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function purchaseShortListAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicinePrepurchaseItem')->getApiPurchaseShortListSearch($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function medicineNameAction(Request $request)
    {
        $process = isset($_REQUEST['process']) ? $_REQUEST['process'] : '';
        $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            if($process == 'brand' and !empty($keyword)){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineBrand')->getApiMedicine($keyword);
            }elseif($process == 'generic' and !empty($keyword)){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineBrand')->getApiMedicineGeneric($keyword);
            }elseif($process == 'strength'){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineBrand')->getApiMedicineStrength($keyword);
            }elseif($process == 'form'){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineBrand')->getApiMedicineForm($keyword);
            }elseif($process == 'manufacture' and !empty($keyword)){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineBrand')->getApiMedicineManufacture($keyword);
            }else{
                return new Response('Unauthorized access.', 401);
            }

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function unitAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $data = $this->getDoctrine()->getRepository('SettingToolBundle:ProductUnit')->getApiUnit();
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function categoryAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineParticularType')->getApiCategory($entity);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function createStockAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $data = $request->request->all();
            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $stock = $this->getDoctrine()->getRepository(AppsApi::class)->apiInsertStock($entity,$data);
            $data = $this->getDoctrine()->getRepository(AppsApi::class)->getApiSingleStock($entity,$stock->getId());
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function editStockAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{

            /* @var $entity GlobalOption */
            $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository(AppsApi::class)->getStockEdit($entity,$id);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function deleteStockAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{

            /* @var $entity GlobalOption */
            $em = $this->getDoctrine()->getManager();
            $stockId = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
            $entity = $this->getDoctrine()->getRepository(MedicineStock::class)->find($stockId);
            if(empty($entity)) {
                $status = 'failed';
            }
            try {
                $em->remove($entity);
                $em->flush();
                $status = 'success';

            } catch (ForeignKeyConstraintViolationException $e) {
                $entity->setStatus(false);
                $entity->setIsDelete(true);
                $em->persist($entity);
                $em->flush();
                $status = 'warning';
            }catch (\Exception $e) {
                $status = 'invalid';
            }
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('message'=>$status, 'ipAddress'=>$ipAddress)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function updateStockAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $data = $request->request->all();
            $entity = $this->checkApiValidation($request);
            $stockId = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
            $this->getDoctrine()->getRepository(AppsApi::class)->apiUpdateStock($entity,$data);
            $data = $this->getDoctrine()->getRepository(AppsApi::class)->getStockEdit($entity,$stockId);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function insertAndroidStockMeasurementAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $data = $request->request->all();
            $stockId = isset($_REQUEST['stockId']) ? $_REQUEST['stockId'] : '';
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $this->getDoctrine()->getRepository('MedicineBundle:MedicineMeasurement')->insertAndroidStockMeasurement($stockId,$data);
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineMeasurement')->getApiMeasurement($stockId);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function deleteAndroidStockMeasurementAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $em = $this->getDoctrine()->getManager();
            $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
            $entity = $em->getRepository('MedicineBundle:MedicineMeasurement')->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find MedicineStock entity.');
            }
            $em->remove($entity);
            $em->flush();
            $stockId = isset($_REQUEST['stockId']) ? $_REQUEST['stockId'] : '';
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineMeasurement')->getApiMeasurement($stockId);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function insertPurchaseShortItemAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $data = $request->request->all();
            $stockId = isset($_REQUEST['stockId']) ? $_REQUEST['stockId'] : '';
            /* @var $entity GlobalOption */
            $id = $this->getDoctrine()->getRepository('MedicineBundle:MedicinePrepurchaseItem')->insertApiPurchaseShortList($stockId);
            $data = array('id' => $id);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function deletePurchaseShortItemAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        $em = $this->getDoctrine()->getManager();
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $stockId = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
            $entity = $this->getDoctrine()->getRepository('MedicineBundle:MedicinePrepurchaseItem')->find($stockId);
            $em->remove($entity);
            $em->flush();
            $data = array('status' => 'success');
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function insertGlobalToLocalAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $option = $this->checkApiValidation($request);
            $stockId = isset($_REQUEST['medicineId']) ? $_REQUEST['medicineId'] : '';
            /* @var $entity GlobalOption */
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineStock')->insertApiGlobalToLocal($option,$stockId);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function transactionMethodAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $result = $this->getDoctrine()->getRepository('SettingToolBundle:TransactionMethod')->findAll();
            $data = array();

            /* @var $row TransactionMethod */

            foreach($result as $key => $row) {
                $data[$key]['item_id']              = (int) $row->getId();
                $data[$key]['name']                 = $row->getName();
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function paymentCardAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $result = $this->getDoctrine()->getRepository('SettingToolBundle:PaymentCard')->findAll();
            $data = array();

            /* @var $row TransactionMethod */

            foreach($result as $key => $row) {
                $data[$key]['item_id']              = (int) $row->getId();
                $data[$key]['name']                 = $row->getName();
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiBankAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $result = $this->getDoctrine()->getRepository('AccountingBundle:AccountBank')->findBy(array('globalOption'=>$entity));

            $data = array();

            /* @var $row AccountBank */

            if($result){
                foreach($result as $key => $row) {
                    $data[$key]['global_id']            = (int) $entity->getId();
                    $data[$key]['item_id']              = (int) $row->getId();
                    $data[$key]['name']                 = $row->getName();
                    $data[$key]['service_charge']       = $row->getServiceCharge();
                }
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiMobileAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $result = $this->getDoctrine()->getRepository('AccountingBundle:AccountMobileBank')->findBy(array('globalOption'=>$entity));
            $data = array();

            /* @var $row AccountMobileBank */

            if($result) {
                foreach ($result as $key => $row) {
                    $data[$key]['global_id'] = (int)$entity->getId();
                    $data[$key]['item_id'] = (int)$row->getId();
                    $data[$key]['name'] = $row->getName();
                    $data[$key]['service_charge'] = $row->getServiceCharge();
                }
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function expenditureCategoryAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('AccountingBundle:ExpenseCategory')->getApiCategory($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function templateCustomizationAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);

            /* @var $template TemplateCustomize */

            $baseurl = "";
            $template = $entity->getTemplateCustomize();
            if($template->getAndroidLogo()){
                $dir = $template->getUploadDir();
                $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath().'/'.$dir.'/'.$template->getAndroidLogo();
            }

            $data = array(
                'globalOption'                      => $entity->getId(),
                'androidLogo'                       => $baseurl,
                'androidHeaderBg'                   => $template->getAndroidHeaderBg() ,
                'androidMenuBg'                     => $template->getAndroidMenuBg() ,
                'androidMenuBgHover'                => $template->getAndroidMenuBgHover() ,
                'androidAnchorColor'                => $template->getAndroidAnchorColor() ,
                'androidAnchorHoverColor'           => $template->getAndroidAnchorHoverColor() ,
           );
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function discountCouponAction(Request $request)
    {

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('RestaurantBundle:Coupon')->getApiDiscountCoupon($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function tokenNoAction(Request $request)
    {

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('RestaurantBundle:Particular')->getApiRestaurantToken($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function apiPingAction(Request $request){

        $data = $request->request->all();
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode(array('message'=>$data['message'], 'ipAddress'=>$ipAddress)));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }

    public function apiResponseAction(Request $request, $data)
    {

    }

    public function apiSalesAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);


        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $data = $request->request->all();
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');

           // $data = array('mode' => "online/offline",'itemCount'=> 1,'itemCount'=> 1,'subItem'=> $jsonInputItem,'subItemCount'=> 4);

            $this->getDoctrine()->getRepository(AppsApi::class)->insertFlutterSalesProcess($entity,$deviceId,'sales',$data);
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('message'=> "success" , 'ipAddress' => $ipAddress)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiInvoiceTokenAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $data = $this->getDoctrine()->getRepository('BusinessBundle:BusinessInvoice')->insertApiInvoiceToken($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode("success"));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function stockDeleteAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $data = $request->request->all();
            $entity = $this->checkApiValidation($request);
            $stockId = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineStock')->getApiStockDelete($entity,$stockId);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->getDoctrine()->getRepository('RestaurantBundle:Particular')->getApiStockDelete($entity,$stockId);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->getDoctrine()->getRepository('InventoryBundle:Item')->getApiStockDelete($entity,$stockId);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->getDoctrine()->getRepository('BusinessBundle:BusinessParticular')->getApiStockDelete($entity,$stockId);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function stockUpdateAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $data = $request->request->all();
            $entity = $this->checkApiValidation($request);
            $stockId = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
            $this->getDoctrine()->getRepository('MedicineBundle:MedicineStock')->updateAndroidStock($data);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineStock')->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->getDoctrine()->getRepository('RestaurantBundle:Particular')->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->getDoctrine()->getRepository('InventoryBundle:Item')->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->getDoctrine()->getRepository('BusinessBundle:BusinessParticular')->getApiStock($entity);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    /* Sales manage */


    public function apiOrderProcessAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');
            $data = $request->request->all();
            $status = $this->getDoctrine()->getRepository(AppsApi::class)->insertFlutterOrderProcess($entity,$deviceId,$data);
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('message'=> $status, 'ipAddress'=>$ipAddress)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiCreateSalesAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');

            $jsonInput = '[
                {
                "invoiceId":"051900113","subTotal":"1200","discount":"200","discountType":"Flat","discountCalculation":"10","total":"1000","receive":"800","due":"200","vendorId":"223","created":"","createdBy":"","invoiceMode":"","process":""
                }
            ]';

            $jsonInputItem = '[
             {"purchaseId":"051900113","stockId":"10717","purchasePrice":"25","quantity":"2","subTotal":"50","measurement_id":1,"unit":"Pcs"}
            ]';

            $data = $request->request->all();

            $status = $this->getDoctrine()->getRepository(AppsApi::class)->insertFlutterSalesProcess($entity,$deviceId,'sales',$data);
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('message'=> $status, 'ipAddress'=>$ipAddress)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiOnlineSalesAction(Request $request)
    {
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');
            $data = $_REQUEST;
            $entities = $this->getDoctrine()->getRepository(AppsApi::class)->apiInventorySales($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function apiOnlineSalesDetailsAction(Request $request)
    {
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');
            $salesId = $_REQUEST['id'];
            $entities = $this->getDoctrine()->getRepository(AppsApi::class)->apiSalesInvoiceDetails($entity,$salesId);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function apiOnlineSalesDeleteAction(Request $request)
    {
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $em = $this->getDoctrine()->getManager();
            $this->checkApiValidation($request);
            $salesId = $_REQUEST['id'];
            $entity = $this->getDoctrine()->getRepository("MedicineBundle:MedicineSales")->find($salesId);
            if(empty($entity)) {
                $status = 'failed';
            }elseif($entity){
                $em->remove($entity);
                $em->flush();
                $status = 'success';
            }else{
                $status = 'failed';
            }
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('message'=>$status, 'ipAddress'=>$ipAddress)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }


    public function customerInvoiceAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $invoice = $request->request->get('invoice');
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineSales')->getApiSalesInvoice($entity,$invoice);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->getDoctrine()->getRepository('RestaurantBundle:Particular')->getApiSalesInvoice($entity,$invoice);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->getDoctrine()->getRepository('InventoryBundle:Item')->getApiSalesInvoice($entity,$invoice);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->getDoctrine()->getRepository('BusinessBundle:BusinessParticular')->getApiSalesInvoice($entity,$invoice);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function salesInvoiceMessageAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineParticular')->apiInvoiceMessage($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->getDoctrine()->getRepository('RestaurantBundle:Particular')->apiInvoiceMessage($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->getDoctrine()->getRepository('InventoryBundle:Item')->apiInvoiceMessage($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->getDoctrine()->getRepository('BusinessBundle:BusinessParticular')->apiInvoiceMessage($entity);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }



    /* Customer manage */

    public function salesReceiveAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $data = $request->request->all();
            /* @var $option GlobalOption */
            $option = $this->checkApiValidation($request);
            $status = $this->getDoctrine()->getRepository(AppsApi::class)->apiInsertSalesReceive($option,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status'=> $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }


    public function apiAccountingSalesDeleteAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $id = $_REQUEST['id'];
            $status = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiDeleteSales($id);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status'=> $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiAccountingSalesApproveAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $form = $_REQUEST;
            $status = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiApproveSales($entity,$form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status'=> $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function accountSalesAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $formArray = $_REQUEST;
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $result = $this->getDoctrine()->getRepository(AppsApi::class)->apiAccountSales($entity,$formArray);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function customerOutstandingAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $data = $_REQUEST;
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $entities = $this->getDoctrine()->getRepository('AccountingBundle:AccountSales')->customerSummary($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function customerLedgerAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $formArray = $_REQUEST;
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $result = $this->getDoctrine()->getRepository(AppsApi::class)->apiAccountSales($entity,$formArray);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function customerAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $data = $_REQUEST;
            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository(AppsApi::class)->getApiCustomerOutstanding($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function customerSalesAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $formArray = $_REQUEST;
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $result = $this->getDoctrine()->getRepository(AppsApi::class)->apiAccountSales($entity,$formArray);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function createCustomerAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $form = $_REQUEST;
            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('DomainUserBundle:Customer')->apiCreateCustomer($entity,$form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function editCustomerAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $form = $_REQUEST;
            $data = $this->getDoctrine()->getRepository('DomainUserBundle:Customer')->apiUpdateCustomer($entity,$form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function updateCustomerAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $this->checkApiValidation($request);
            $form = $_REQUEST;
            $data = $this->getDoctrine()->getRepository('DomainUserBundle:Customer')->apiUpdateCustomer($form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    /* Manage Vendor */


    public function apiCreatePurchaseAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');
            $data = $request->request->all();
            $status = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->insertFlutterPurchaseProcess($entity,$deviceId,'purchase',$data);
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('message'=> $status, 'ipAddress'=>$ipAddress)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiOnlinePurchaseAction(Request $request)
    {
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');
            $data = $_REQUEST;
            $entities = $this->getDoctrine()->getRepository("ReportBundle:AppsApi")->apiInventoryPurchase($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function apiOnlinePurchaseDetailsAction(Request $request)
    {
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');
            $salesId = $_REQUEST['id'];
            $entities = $this->getDoctrine()->getRepository("ReportBundle:AppsApi")->apiPurchaseInvoiceDetails($entity,$salesId);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function apiOnlinePurchaseDeleteAction(Request $request)
    {
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $em = $this->getDoctrine()->getManager();
            $this->checkApiValidation($request);
            $salesId = $_REQUEST['id'];
            $entity = $this->getDoctrine()->getRepository("MedicineBundle:MedicinePurchase")->find($salesId);
            if(empty($entity)) {
                $status = 'failed';
            }elseif($entity){
                $em->remove($entity);
                $em->flush();
                $status = 'success';
            }else{
                $status = 'failed';
            }
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('message'=>$status, 'ipAddress'=>$ipAddress)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function purchasePaymentAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $data = $request->request->all();
            /* @var $option GlobalOption */
            $option = $this->checkApiValidation($request);
            $status = $this->getDoctrine()->getRepository("ReportBundle:AppsApi")->apiInsertPurchasePayment($option,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status' => $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiAccountingPurchaseDeleteAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $id = $_REQUEST['id'];
            $status = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiDeleteAccountPurchase($id);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status' => $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiAccountingPurchaseApproveAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $form = $_REQUEST;
            $status = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiApproveAccountPurchase($entity,$form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status' => $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function vendorPurchaseAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $formArray = $_REQUEST;
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $result = $this->getDoctrine()->getRepository(AppsApi::class)->apiAccountPurchase($entity,$formArray);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function vendorOutstandingAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{
            $data = $_REQUEST;
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $entities = $this->getDoctrine()->getRepository('AccountingBundle:AccountPurchase')->apiVendorMedicineOutstanding($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function vendorLedgerAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            $formArray = $_REQUEST;
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $result = $this->getDoctrine()->getRepository(AppsApi::class)->apiAccountPurchase($entity,$formArray);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function vendorAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', 401);
        }else{

            /* @var $entity GlobalOption */
            $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineVendor')->getApiVendor($entity,$keyword);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function createVendorAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $form = $_REQUEST;
            $entity = $this->checkApiValidation($request);
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineVendor')->apiCreateVendor($entity,$form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function editVendorAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $form = $_REQUEST;
            $data = $this->getDoctrine()->getRepository('MedicineBundle:MedicineVendor')->apiGetVendor($entity,$form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function updateVendorAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $this->checkApiValidation($request);
            $form = $_REQUEST;
            $data = $this->getDoctrine()->getRepository('AccountingBundle:AccountVendor')->apiUpdateVendor($form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    /* Manage Expenditure */

    public function apiExpenseBatchAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $deviceId = $request->headers->get('X-DEVICE-ID');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $this->getDoctrine()->getRepository('AccountingBundle:ExpenseAndroidProcess')->insertAndroidProcess($entity,$ipAddress,$deviceId,'expenditure',$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('message'=>"success", 'ipAddress'=>$ipAddress)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiExpenseAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $deviceId = $request->headers->get('X-DEVICE-ID');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $data = $_REQUEST;
            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $entities = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiExpense($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;

        }

    }

    public function apiExpenseCreateAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $deviceId = $request->headers->get('X-DEVICE-ID');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $status =$this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiInsertExpense($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status' => $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiExpenseDeleteAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $id = $_REQUEST['id'];
            $status = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiDeleteExpense($entity,$id);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status'=>$status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiExpenseApproveAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $form = $_REQUEST;
            $status = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiApproveExpense($entity,$form);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status' => $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiExpenseCategoryAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $deviceId = $request->headers->get('X-DEVICE-ID');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $data = $_REQUEST;
            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $entities = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiExpenseCategory($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;

        }

    }

    public function apiExpenseHeadAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $deviceId = $request->headers->get('X-DEVICE-ID');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $data = $_REQUEST;
            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $entities = $this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiExpenseHead($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($entities));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;

        }

    }

    /* Manage Expenditure */

    public function apiLogsAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $deviceId = $request->headers->get('X-DEVICE-ID');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', 401);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $status =$this->getDoctrine()->getRepository('ReportBundle:AppsApi')->apiInsertLogs($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(array('status' => $status)));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }


}
