<?php

namespace App\Bindu\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }


    public function checkApiValidation($request)
    {

        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        $unique = $request->headers->get('X-API-SECRET');
        $setup = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->findOneBy(['uniqueCode' => $unique,'status'=>1]);
        if (!empty($setup) and $request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            return $setup;
        }
        return "invalid";
    }

    public function setupAction(Request $request)
    {

        $formData = $request->request->all();
        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        $uniqueCode = $formData['uniqueCode'];
        $mobile = $formData['mobile'];
        $deviceId = $formData['deviceId'];
        $data = [];
        $entity = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->findOneBy(['uniqueCode' => $uniqueCode,'mobile' => $mobile,'status'=>1]);
        if (empty($entity) and $request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
                return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);
        }else{

            /* @var $entity GlobalOption */

            $device = $this->em->getRepository(\App\Tool\Entity\AndroidDeviceSetup::class)->insert($entity,$deviceId);
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
                $data = [
                    'setupId' => $entity->getId(),
                    'deviceId' => $device,
                    'uniqueCode' => $entity->getUniqueCode(),
                    'name' => $entity->getName(),
                    'mobile' => $mobile,
                    'email' => $entity->getEmail(),
                    'locationId' => $entity->getLocation()->getId(),
                    'address' => $address,
                    'locationName' => $address,
                    'printFooter' => $printFooter,
                    'main_app' => $entity->getMainApp()->getId(),
                    'main_app_name' => $entity->getMainApp()->getSlug(),
                    'appsManual' => $entity->getMainApp()->getApplicationManual(),
                    'website' => $entity->getDomain(),
                    'vatRegNo' => $vatRegNo,
                    'vatPercentage' => $vatPercentage,
                    'vatEnable' => $vatEnable,
                ];
            }
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }


    public function hex6ToHex8($hex6) {
        return str_replace("#","0xFF",$hex6);
    }

    public function random_color_code() {
        $red = str_pad(dechex(random_int(0, 255)), 2, '0', STR_PAD_LEFT);
        $green = str_pad(dechex(random_int(0, 255)), 2, '0', STR_PAD_LEFT);
        $blue = str_pad(dechex(random_int(0, 255)), 2, '0', STR_PAD_LEFT);
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
        $data = [];
        $entity = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->findOneBy(['uniqueCode' => $uniqueCode,'mobile' => $mobile,'status'=>1]);
        if (empty($entity) and $request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);
        }else {

            $device = $this->em->getRepository(\App\Tool\Entity\AndroidDeviceSetup::class)->insert($entity, $deviceId);
            if ($device) {
                /* @var $entity GlobalOption */

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
                    if ($footerMessage) {
                        $printFooter = $footerMessage;
                    } else {
                        $printFooter = "Thanks For Visiting our pharmacy";
                    }
                } elseif ($entity->getMainApp()->getSlug() == "business") {
                    $address = $entity->getBusinessConfig()->getAddress();
                    $vatPercentage = $entity->getBusinessConfig()->getVatPercentage();
                    $vatRegNo = $entity->getBusinessConfig()->getVatRegNo();
                    $vatEnable = $entity->getBusinessConfig()->getVatEnable();
                    $footerMessage = $entity->getBusinessConfig()->getPrintFooterText();
                    if ($footerMessage) {
                        $printFooter = $footerMessage;
                    } else {
                        $printFooter = "Thanks For Visiting our business store";
                    }
                } elseif ($entity->getMainApp()->getSlug() == "restaurant") {
                    $address = $entity->getRestaurantConfig()->getAddress();
                    $vatPercentage = $entity->getRestaurantConfig()->getVatPercentage();
                    $vatRegNo = $entity->getRestaurantConfig()->getVatRegNo();
                    $vatEnable = $entity->getRestaurantConfig()->getVatEnable();
                    $footerMessage = $entity->getRestaurantConfig()->getPrintFooterText();
                    if ($footerMessage) {
                        $printFooter = $footerMessage;
                    } else {
                        $printFooter = "Thanks For Visiting our restaurant";
                    }
                } elseif ($entity->getMainApp()->getSlug() == "inventory") {
                    $address = $entity->getInventoryConfig()->getAddress();
                    $vatPercentage = $entity->getInventoryConfig()->getVatPercentage();
                    $vatRegNo = $entity->getInventoryConfig()->getVatRegNo();
                    $vatEnable = $entity->getInventoryConfig()->getVatEnable();
                    $footerMessage = $entity->getInventoryConfig()->getPrintFooterText();
                    if ($footerMessage) {
                        $printFooter = $footerMessage;
                    } else {
                        $printFooter = "Thanks For Visiting our shop";
                    }
                }
                $mobile = empty($entity->getHotline()) ? $entity->getMobile() : $entity->getHotline();
                $androidHeaderBg = (string)trim($entity->getTemplateCustomize()->getMobileHeaderBgColor());
                $bodyBg = (string)trim($entity->getTemplateCustomize()->getBodyColor());
                $appPrimaryColor = (string)trim($entity->getTemplateCustomize()->getAppPrimaryColor());
                $appSecondaryColor = (string)trim($entity->getTemplateCustomize()->getAppSecondaryColor());
                $appBarColor = (string)trim($entity->getTemplateCustomize()->getAppBarColor());
                $appTextTitle = (string)trim($entity->getTemplateCustomize()->getAppTextTitle());
                $appTextColor = (string)trim($entity->getTemplateCustomize()->getAppTextColor());
                $appCartColor = (string)trim($entity->getTemplateCustomize()->getAppCartColor());
                $appMoreColor = (string)trim($entity->getTemplateCustomize()->getAppMoreColor());
                $appDiscountColor = (string)trim($entity->getTemplateCustomize()->getAppDiscountColor());
                $appBorderActiveColor = (string)trim($entity->getTemplateCustomize()->getAppBorderActiveColor());
                $appBorderInactiveColor = (string)trim($entity->getTemplateCustomize()->getAppBorderInactiveColor());
                $appBorderColor = (string)trim($entity->getTemplateCustomize()->getAppBorderColor());
                $appPositiveColor = (string)trim($entity->getTemplateCustomize()->getAppPositiveColor());
                $appNegativeColor = (string)trim($entity->getTemplateCustomize()->getAppNegativeColor());
                $appIconColor = (string)trim($entity->getTemplateCustomize()->getAndroidIconColor());
                $appAnchorColor = (string)trim($entity->getTemplateCustomize()->getAndroidAnchorColor());
                $appAnchorHoverColor = (string)trim($entity->getTemplateCustomize()->getAndroidAnchorHoverColor());
                $searchPageBgColor = (string)trim($entity->getTemplateCustomize()->getSearchPageBgColor());
                $appFooterBgColor = (string)trim($entity->getTemplateCustomize()->getMobileFooterBgColor());
                $appFooterIconBgColor = (string)trim($entity->getTemplateCustomize()->getMobileFooterAnchorBg());
                $appFooterIconColor = (string)trim($entity->getTemplateCustomize()->getMobileFooterAnchorColor());
                $appFooterIconColorHover = (string)trim($entity->getTemplateCustomize()->getMobileFooterAnchorColorHover());

                $appSuccessColor = (string)trim($entity->getTemplateCustomize()->getAppSuccessColor());
                $appNoticeColor = (string)trim($entity->getTemplateCustomize()->getAppNoticeColor());
                $appCloseColor = (string)trim($entity->getTemplateCustomize()->getAppCloseColor());
                $morePageColor = (string)trim($entity->getTemplateCustomize()->getAppMoreColor());
                $inputBgColor = (string)trim($entity->getTemplateCustomize()->getInputBgColor());
                $inputBgFocusColor = (string)trim($entity->getTemplateCustomize()->getInputBgFocusColor());

                $tawk = (string)trim($entity->getTemplateCustomize()->getTawk());
                $pixel = (string)trim($entity->getTemplateCustomize()->getFacebookPixel());
                $messenger = (string)trim($entity->getTemplateCustomize()->getFbMessenger());
                $analytic = (string)trim($entity->getTemplateCustomize()->getGoogleAnalytic());

                $logo = empty($entity->getTemplateCustomize()->getWebPath('logo')) ? "" : $entity->getTemplateCustomize()->getWebPath('logo');
                //$introImage = empty($entity->getTemplateCustomize()->getWebPath('androidLogo')) ? '': $entity->getTemplateCustomize()->getWebPath('androidLogo');
                $logo = '';
                $introImage = '';

                $data = [

                    'deviceId' => $device,
                    'vatEnable' => $vatEnable,
                    'setupId' => $entity->getId(),
                    'uniqueCode' => $entity->getUniqueCode(),
                    'name' => $entity->getName(),
                    'mobile' => $mobile,
                    'tawk' => $this->stringNullChecker($tawk),
                    'pixel' => $this->stringNullChecker($pixel),
                    'messenger' => $this->stringNullChecker($messenger),
                    'analytic' => $this->stringNullChecker($analytic),
                    'email' => $this->stringNullChecker($entity->getEmail()),
                    'locationId' => $entity->getLocation()->getId(),
                    'address' => $this->stringNullChecker($address),
                    'locationName' => $this->stringNullChecker($entity->getLocation()->getName()),
                    'main_app' => $entity->getMainApp()->getId(),
                    'main_app_name' => $entity->getMainApp()->getSlug(),
                    'mainApp' => $entity->getMainApp()->getId(),
                    'mainAppName' => $entity->getMainApp()->getSlug(),
                    'appsManual' => $this->stringNullChecker($entity->getMainApp()->getApplicationManual()),
                    'website' => $this->stringNullChecker($entity->getDomain()),
                    'vatRegNo' => $this->stringNullChecker($vatRegNo),
                    'vatPercentage' => $this->numberNullChecker($vatPercentage),
                    'bodyBg' => empty($bodyBg) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($bodyBg),
                    'appHeaderBg' => empty($androidHeaderBg) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($androidHeaderBg),
                    'appPrimaryColor' => empty($appPrimaryColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appPrimaryColor),
                    'appSecondaryColor' => empty($appSecondaryColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appSecondaryColor),
                    'appBarColor' => empty($appBarColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appBarColor),
                    'appTextTitle' => empty($appTextTitle) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appTextTitle),
                    'appTextColor' => empty($appTextColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appTextColor),
                    'appCartColor' => empty($appCartColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appCartColor),
                    'appMoreColor' => empty($appMoreColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appMoreColor),
                    'appBorderColor' => empty($appBorderColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appBorderColor),
                    'appBorderActiveColor' => empty($appBorderActiveColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appBorderActiveColor),
                    'appBorderInactiveColor' => empty($appBorderInactiveColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appBorderInactiveColor),
                    'appPositiveColor' => empty($appPositiveColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appPositiveColor),
                    'appNegativeColor' => empty($appNegativeColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appNegativeColor),
                    'appDiscountColor' => empty($appDiscountColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appDiscountColor),
                    'appIconColor' => empty($appIconColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appIconColor),
                    'appAnchorColor' => empty($appAnchorColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appAnchorColor),
                    'appAnchorHoverColor' => empty($appAnchorHoverColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appAnchorHoverColor),
                    'searchPageBgColor' => empty($searchPageBgColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($searchPageBgColor),
                    'morePageBgColor' => empty($morePageColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($morePageColor),
                    'appFooterBgColor' => empty($appFooterBgColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appFooterBgColor),
                    'appFooterIconBgColor' => empty($appFooterIconBgColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appFooterIconBgColor),
                    'appFooterIconColor' => empty($appFooterIconColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appFooterIconColor),
                    'appFooterIconColorHover' => empty($appFooterIconColorHover) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appFooterIconColorHover),
                    'appSuccessColor' => empty($appSuccessColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appSuccessColor),
                    'appNoticeColor' => empty($appNoticeColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appNoticeColor),
                    'appCloseColor' => empty($appCloseColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($appCloseColor),
                    'inputBgColor' => empty($inputBgColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($inputBgColor),
                    'inputBgFocusColor' => empty($inputBgFocusColor) ? $this->hex6ToHex8($this->random_color_code()) : $this->hex6ToHex8($inputBgFocusColor),
                    // 'logo'      =>  $this->imageBase64($logo),
                    'logo' => '',
                    'intro' => '',
                ];
            }

            $slides = [];
            $introPages = [];
            $users = $this->em->getRepository(\App\User\Entity\User::class)->getCustomers($entity);
            $customers = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->customerOutstanding($entity);

            if($entity->getMainApp()->getSlug() == 'miss'){
                $stocks = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getApiSpalshStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $stocks = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $stocks = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $stocks = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiStock($entity);
            }

            if($entity->getMainApp()->getSlug() == 'miss'){
                $categories = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->getApiCategory($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $categories = $this->em->getRepository(\App\Restaurant\Entity\Category::class)->getApiCategory($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $categories = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiCategory($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $categories = $this->em->getRepository(\App\Business\Entity\Category::class)->getApiCategory($entity);
            }

            if($entity->getMainApp()->getSlug() == 'miss'){
                $vendors = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->getApiVendor($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $vendors = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->getApiVendor($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $vendors = $this->em->getRepository(\App\Inventory\Entity\Vendor::class)->getApiVendor($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $vendors = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->getApiVendor($entity);
            }

            $result = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findAll();
            $methods = [];

            /* @var $row TransactionMethod */

            foreach($result as $key => $row) {
                $methods[$key]['item_id']              = (int) $row->getId();
                $methods[$key]['name']                 = $row->getName();
            }

            $paymentCards = $this->em->getRepository(\App\Tool\Entity\PaymentCard::class)->findAll();

            $cards = [];

            /* @var $row PaymentCard */

            foreach($paymentCards as $key => $row) {
                $cards[$key]['item_id']              = (int) $row->getId();
                $cards[$key]['name']                 = $row->getName();
            }

            $accountBanks = $this->em->getRepository(\App\Accounting\Entity\AccountBank::class)->findBy(['globalOption'=>$entity,'status'=>1]);

            $banks = [];

            /* @var $row AccountBank */

            if($accountBanks){
                foreach($accountBanks as $key => $row) {
                    $banks[$key]['global_id']            = (int) $entity->getId();
                    $banks[$key]['item_id']              = (int) $row->getId();
                    $banks[$key]['name']                 = $row->getName();
                    $banks[$key]['service_charge']       = $row->getServiceCharge();
                }
            }

            $accountMobileBanks = $this->em->getRepository(\App\Accounting\Entity\AccountMobileBank::class)->findBy(['globalOption'=>$entity,'status'=>1]);
            $mobiles = [];

            /* @var $row AccountMobileBank */

            if($accountMobileBanks) {
                foreach ($accountMobileBanks as $key => $row) {
                    $mobiles[$key]['global_id'] = (int)$entity->getId();
                    $mobiles[$key]['item_id'] = (int)$row->getId();
                    $mobiles[$key]['name'] = $row->getName();
                    $mobiles[$key]['service_charge'] = $row->getServiceCharge();
                }
            }


        }
        $jsonData = [
            'setup'             => $data,
            'users'             => $users,
            'customers'         => $customers,
            'categories'        => $categories,
            'vendors'           => $vendors,
            'stocks'            => $stocks,
            'transaction_methods' => $methods,
            'payment_cards' => $cards,
            'bank_accounts' => $banks,
            'mobile_accounts' => $mobiles,

        ];
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($jsonData));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

    public function systemUsersAction(Request $request)
    {

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $entity = $this->checkApiValidation($request);
            $data = $this->em->getRepository(\App\User\Entity\User::class)->getCustomers($entity);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function dashboardAction(Request $request)
    {

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');
            $datetime = new DateTime("now");
            $data['startDate'] = $datetime->format('Y-m-d');
            $data['endDate'] = $datetime->format('Y-m-d');
            $data['device'] = $deviceId;
            $purchaseCashOverview = $this->em->getRepository(\App\Medicine\Entity\MedicinePurchase::class)->androidDevicePurchaseOverview($entity,$data);
            $salesCashOverview = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->androidDeviceSalesOverview($entity,$data);
            $expenditureOverview = $this->em->getRepository(\App\Accounting\Entity\Expenditure::class)->androidDeviceExpenditureOverview($entity,$data);
            $data = [
                'globalOption'              => $entity->getId(),
                'expenditureOverview'       => $expenditureOverview ,
                'totalSales'                => $salesCashOverview['total'] ,
                'salesReceive'              => $salesCashOverview['salesReceive'] ,
                'salesVoucher'              => (string) $salesCashOverview['voucher'] ,
                'purchaseTotal'             => $purchaseCashOverview['total'] ,
                'purchasePayment'           => $purchaseCashOverview['payment'] ,
            ];

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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiStock($entity);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function customerAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->getApiCustomer($entity);
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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $data = $this->em->getRepository(\App\Tool\Entity\ProductUnit::class)->getApiUnit();
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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->em->getRepository(\App\Medicine\Entity\MedicineParticularType::class)->getApiCategory($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->em->getRepository(\App\Restaurant\Entity\Category::class)->getApiCategory($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiCategory($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->em->getRepository(\App\Business\Entity\Category::class)->getApiCategory($entity);
            }

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function vendorAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);
        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->em->getRepository(\App\Medicine\Entity\MedicineVendor::class)->getApiVendor($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->getApiVendor($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->em->getRepository(\App\Inventory\Entity\Vendor::class)->getApiVendor($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->em->getRepository(\App\Accounting\Entity\AccountVendor::class)->getApiVendor($entity);
            }

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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $result = $this->em->getRepository(\App\Tool\Entity\TransactionMethod::class)->findAll();
            $data = [];

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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $result = $this->em->getRepository(\App\Tool\Entity\PaymentCard::class)->findAll();
            $data = [];

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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $result = $this->em->getRepository(\App\Accounting\Entity\AccountBank::class)->findBy(['globalOption'=>$entity,'status'=>1]);

            $data = [];

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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $result = $this->em->getRepository(\App\Accounting\Entity\AccountMobileBank::class)->findBy(['globalOption'=>$entity,'status'=>1]);
            $data = [];

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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $this->em->getRepository(\App\Accounting\Entity\ExpenseCategory::class)->getApiCategory($entity);
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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

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

            $data = [
                'globalOption'                      => $entity->getId(),
                'androidLogo'                       => $baseurl,
                'androidHeaderBg'                   => $template->getAndroidHeaderBg() ,
                'androidMenuBg'                     => $template->getAndroidMenuBg() ,
                'androidMenuBgHover'                => $template->getAndroidMenuBgHover() ,
                'androidAnchorColor'                => $template->getAndroidAnchorColor() ,
                'androidAnchorHoverColor'           => $template->getAndroidAnchorHoverColor() ,
           ];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function medicineDimsAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = ['offset' => 0,'limit' => 2000];
            $data = $this->em->getRepository(\App\Medicine\Entity\MedicineBrand::class)->getApiDims($entity,$data);
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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $entity = $this->checkApiValidation($request);
            $data = $this->em->getRepository(\App\Restaurant\Entity\Coupon::class)->getApiDiscountCoupon($entity);
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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $entity = $this->checkApiValidation($request);
            $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiRestaurantToken($entity);
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
        $response->setContent(json_encode(['message'=>$data['message'], 'ipAddress'=>$ipAddress]));
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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{


            $jsonInput = '[
            {
            "invoiceId":"051900113","subTotal":"1200","discount":"200","discountType":"Flat","discountCalculation":"10","vat":"0","total":"1000","receive":"800","due":"200","customerId":"223","customerName":"Jacky","customerMobile":"01828148148","addresss":"Dhaka","transactionMethod":"cash","bankAccount":"","mobileBankAccount":"","paymentMobile":"","paymentCard":"","paymentCardNo":"","transactionId":"","salesBy":"","created":"","createdBy":"","slipNo":"","tokenNo":"","discountCoupon":"","remark":""
            }
        ]';

        $jsonInputItem = '[
            {"salesId":"051900113","stockId":"7087","unitPrice":"120","quantity":"2","subTotal":"240"},
            {"salesId":"051900113","stockId":"1295","unitPrice":"120","quantity":"2","subTotal":"240"},
            {"salesId":"051900113","stockId":"7088","unitPrice":"120","quantity":"2","subTotal":"240"},
            {"salesId":"051900113","stockId":"7420","unitPrice":"120","quantity":"2","subTotal":"240"}
        ]';



            $data = $request->request->all();

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');

           // $data = array('item' => $jsonInput,'itemCount'=> 1,'subItem'=> $jsonInputItem,'subItemCount'=> 4);


            if($entity->getMainApp()->getSlug() == 'miss'){
                $this->em->getRepository(\App\Medicine\Entity\MedicineAndroidProcess::class)->insertAndroidProcess($entity,$deviceId,'sales',$data);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $this->em->getRepository(\App\Restaurant\Entity\RestaurantAndroidProcess::class)->insertAndroidProcess($entity,$deviceId,'sales',$data);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $this->em->getRepository(\App\Inventory\Entity\InventoryAndroidProcess::class)->insertAndroidProcess($entity,$deviceId,'sales',$data);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $this->em->getRepository(\App\Business\Entity\BusinessAndroidProcess::class)->insertAndroidProcess($entity,$deviceId,'sales',$data);
            }
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(['message'=> "success", 'ipAddress' => $ipAddress]));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiPurchaseAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);


        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $deviceId = $request->headers->get('X-DEVICE-ID');

            $jsonInput = '[
            {
            "invoiceId":"051900113","subTotal":"1200","discount":"200","discountType":"Flat","discountCalculation":"10","vat":"0","total":"1000","receive":"800","due":"200","vendorId":"223","transactionMethod":"cash","bankAccount":"","mobileBankAccount":"","created":"","createdBy":"","invoiceMode":"","remark":""
            }
        ]';

            $jsonInputItem = '[
            {"purchaseId":"051900113","stockId":"10717","purchasePrice":"25","quantity":"2","subTotal":"50"},
            {"purchaseId":"051900113","stockId":"10713","purchasePrice":"15","quantity":"2","subTotal":"30"},
            {"purchaseId":"051900113","stockId":"10707","purchasePrice":"120","quantity":"2","subTotal":"240"},
            {"purchaseId":"051900113","stockId":"10708","purchasePrice":"10","quantity":"2","subTotal":"20"}
        ]';


           // $data = array('deviceId' => 1,'item' => $jsonInput ,'itemCount'=> 1,'subItem'=>$jsonInputItem,'subItemCount'=> 4);

            $data = $request->request->all();
            if($entity->getMainApp()->getSlug() == 'miss'){
                $this->em->getRepository(\App\Medicine\Entity\MedicineAndroidProcess::class)->insertAndroidProcess($entity,$deviceId,'purchase',$data);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $this->em->getRepository(\App\Restaurant\Entity\RestaurantAndroidProcess::class)->insertAndroidProcess($entity,$deviceId,'purchase',$data);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $this->em->getRepository(\App\Inventory\Entity\InventoryAndroidProcess::class)->insertAndroidProcess($entity,$deviceId,'purchase',$data);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $this->em->getRepository(\App\Business\Entity\BusinessAndroidProcess::class)->insertAndroidProcess($entity,$deviceId,'purchase',$data);
            }
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(['message'=>"success", 'ipAddress'=>$ipAddress]));
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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $this->em->getRepository(\App\Accounting\Entity\ExpenseAndroidProcess::class)->insertAndroidProcess($entity,$ipAddress,$deviceId,'expenditure',$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(['message'=>"success", 'ipAddress'=>$ipAddress]));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function apiInvoiceTokenAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            $data = $request->request->all();
            $data = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->insertApiInvoiceToken($entity,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode("success"));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function createStockAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $formArray = $request->request->all();
            /* @var $entity GlobalOption */

            $entity = $this->checkApiValidation($request);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->insertAndroidStock($entity,$formArray);
                $data = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiStock($entity);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function stockEditAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {
            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);
        }else{

            /* @var $entity GlobalOption */
            $id = $_REQUEST['id'] ?? '';
            $entity = $this->checkApiValidation($request);
            $stock = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->find($id);
            $array  = [];
            if($stock){
                /* @var $stock MedicineStock */
                $array = [
                    'stockId'=> $stock->getId(),
                    'name' => $stock->getName(),
                    'category' => ($stock->getCategory()) ? $stock->getCategory()->getName() : "",
                    'brandName' => $stock->getBrandName(),
                    'price' => $stock->getSalesPrice(),
                    'unit' => ($stock->getUnit()) ? $stock->getUnit()->getName() : "",
                    'minQuantity' => $stock->getMinQuantity()
                ];
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($array));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function stockUpdateAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $data = $request->request->all();
            $entity = $this->checkApiValidation($request);
            $stockId = $_REQUEST['id'] ?? '';
            $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->updateAndroidStock($data);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiStock($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiStock($entity);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function stockDeleteAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $data = $request->request->all();
            $entity = $this->checkApiValidation($request);
            $stockId = $_REQUEST['id'] ?? '';
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->em->getRepository(\App\Medicine\Entity\MedicineStock::class)->getApiStockDelete($entity,$stockId);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiStockDelete($entity,$stockId);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiStockDelete($entity,$stockId);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiStockDelete($entity,$stockId);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function createCustomerAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);

        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $formArray = $request->request->all();
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $status = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->apiCreateCustomer($entity,$formArray);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(['status'=> $status]));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function updateCustomerAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $formArray = $request->request->all();
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $status = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->apiUpdateCustomer($entity,$formArray);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(['status'=> $status]));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

    public function customerLedgerAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $formArray = $request->request->all();
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            $result = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->apiCustomerLedger($entity,$formArray);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function customerInvoiceAction(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $invoice = $request->request->get('invoice');
            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->em->getRepository(\App\Medicine\Entity\MedicineSales::class)->getApiSalesInvoice($entity,$invoice);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->getApiSalesInvoice($entity,$invoice);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->getApiSalesInvoice($entity,$invoice);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->getApiSalesInvoice($entity,$invoice);
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

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            /* @var $entity GlobalOption */
            $entity = $this->checkApiValidation($request);
            if($entity->getMainApp()->getSlug() == 'miss'){
                $data = $this->em->getRepository(\App\Medicine\Entity\MedicineParticular::class)->apiInvoiceMessage($entity);
            }elseif($entity->getMainApp()->getSlug() == 'restaurant'){
                $data = $this->em->getRepository(\App\Restaurant\Entity\Particular::class)->apiInvoiceMessage($entity);
            }elseif($entity->getMainApp()->getSlug() == 'inventory'){
                $data = $this->em->getRepository(\App\Inventory\Entity\Item::class)->apiInvoiceMessage($entity);
            }elseif($entity->getMainApp()->getSlug() == 'business'){
                $data = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->apiInvoiceMessage($entity);
            }
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($data));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }
    }

    public function salesReceiveAction(Request $request)
    {

        set_time_limit(0);
        ignore_user_abort(true);
        if( $this->checkApiValidation($request) == 'invalid') {

            return new Response('Unauthorized access.', Response::HTTP_UNAUTHORIZED);

        }else{

            $data = $request->request->all();
            /* @var $option GlobalOption */
            $option = $this->checkApiValidation($request);
            $status = $this->em->getRepository(\App\Accounting\Entity\AccountSales::class)->apiInsertSalesReceive($option,$data);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode(['status'=> $status]));
            $response->setStatusCode(Response::HTTP_OK);
            return $response;
        }

    }

}
