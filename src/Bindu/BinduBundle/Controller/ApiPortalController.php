<?php

namespace Bindu\BinduBundle\Controller;

use Appstore\Bundle\AccountingBundle\Entity\AccountBank;
use Appstore\Bundle\AccountingBundle\Entity\AccountHead;
use Appstore\Bundle\AccountingBundle\Entity\AccountMobileBank;
use Core\UserBundle\Entity\Profile;
use Core\UserBundle\Entity\User;
use Setting\Bundle\AppearanceBundle\Entity\TemplateCustomize;
use Setting\Bundle\ToolBundle\Entity\GlobalOption;
use Setting\Bundle\ToolBundle\Entity\TransactionMethod;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ApiPortalController extends Controller
{


    public function portalInformationAction(Request $request)
    {

        $formData = $request->request->all();
        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        if ($request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            $data = array(
                'name' => 'Right Brain Solution Ltd.',
                'mobile' => '01828148148',
                'email' => 'info@rightbrainsolution.com',
                'website' => 'www.rightbrainsolution.com',
                'address' => "Pul Tower, Plot no 29, Gausul Azam Avenue, Sector-14, Uttara, Dhaka-1230",
                'lat' => "23.869141",
                'lon' => "90.389381",

            );
        }else{
            return new Response('Unauthorized access.', 401);

        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    public function domainListAction(Request $request)
    {

        $formData = $request->request->all();
        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        if ($request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            $data = $this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->apiDomains();
        }else{
            return new Response('Unauthorized access.', 401);

        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    public function appsAction(Request $request)
    {

        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        if ($request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
          //  $data =$this->getDoctrine()->getRepository('SettingToolBundle:AppModule')->getAppModules();
            $data = $this->getAppModules();
        }else{
            return new Response('Unauthorized access.', 401);
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    public function getAppModules(){
        $appList = array();
        $array = array(
            "medicine" => "Medicine",
            "restaurant"=> "Restaurant",
            "fast-food"=> "Fast-Food",
            "trading"=> "Trading",
            "aggro-feed"=> "Aggro Feed",
            "poultry"=> "Poultry",
            "electronic"=> "Electronic",
            "mobile-shop"=> "Mobile Shop",
            "invoicing"=> "Invoicing",
            "inventory"=> "Inventory",
            "digital-sign"=> "Digital-Sign",
            "parlour"=> "Parlour",
            "fuel-station"=> "Fuel-station",
            "Others"=> "Others",
        );
        $array = array(
            array(
                'app_slug' => 'medicine',
                'name' => "Medicine",
                'title' => "Medicine Management System",
                'license_no' => "01820584047",
                'active_key' => "1671288227",
                'username' => "Pharmacy",
                'password' => "@123456",
                'content' => "<ul>
                        <li><strong>Inventory Tracking:</strong> Real-time monitoring of stock levels, expiry dates, and batch numbers for medicines.</li>
                        <li><strong>Barcode Scanning:</strong> Easy identification and billing of products using barcode scanning.</li>
                        <li><strong>Prescription Management:</strong> Integration to store, process, and validate prescriptions.</li>
                        <li><strong>Vendor Management:</strong> Streamline ordering and manage supplier relationships.</li>
                        <li><strong>Sales & Billing:</strong> Quick billing with automated GST/VAT calculations.</li>
                        <li><strong>Compliance Reporting:</strong> Generate detailed reports for regulatory compliance and audits.</li>
                        <li><strong>Customer Management:</strong> Maintain customer records for loyalty programs and purchase history.</li>
                    </ul>",
            ),
             array(
                'app_slug' => 'restaurant',
                'name' => "Restaurant",
                'title' => "Restaurant Management System",
                'license_no' => "01852892044",
                'active_key' => "1551378444",
                'username' => "jp_rubel",
                'password' => "@123456",
                'content' => "<ul>
            <li><strong>Table Management:</strong> Visual table layouts for reservations and order tracking.</li>
            <li><strong>Order Management:</strong> Integrated order tracking for dine-in, takeaway, and delivery.</li>
            <li><strong>Kitchen Display System (KDS):</strong> Instant order communication between waitstaff and the kitchen.</li>
            <li><strong>Menu Customization:</strong> Update prices, availability, and offers in real-time.</li>
            <li><strong>Payment Integration:</strong> Supports multiple payment methods, including cards, wallets, and UPI.</li>
            <li><strong>Customer Feedback:</strong> Collect and manage customer reviews and ratings.</li>
        </ul>",
            ),
             array(
                'app_slug' => 'invoice',
                'name' => "Business Invoice",
                'title' => "Trading Business POS System",
                'license_no' => "01706250725",
                'active_key' => "1577633987",
                'username' => "rpf_kajol",
                'password' => "@123456",
                'content' => "<ul>
            <li><strong>Multi-Warehouse Management:</strong> Track stock across multiple warehouses and locations.</li>
            <li><strong>Dynamic Pricing:</strong> Flexible pricing strategies based on market trends and demand.</li>
            <li><strong>Purchase Order Management:</strong> Automate purchase orders and vendor coordination.</li>
            <li><strong>Customer Relationship Management (CRM):</strong> Maintain a database for repeat clients and bulk orders.</li>
            <li><strong>Sales Analytics:</strong> Generate detailed sales reports to identify high-performing products.</li>
        </ul>",
            )
        );
        return $array;
    }


    public function appDetailsAction(Request $request)
    {

        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        $id = $_REQUEST['id'];
        if ($request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            $data =$this->getDoctrine()->getRepository('SettingToolBundle:AppModule')->getAppModuleDetails($id);
        }else{
            return new Response('Unauthorized access.', 401);
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }
    public function appWiseCustomerAction(Request $request)
    {

        $key =  $this->getParameter('x-api-key');
        $value =  $this->getParameter('x-api-value');
        $id = $_REQUEST['id'];
        if ($request->headers->get('X-API-KEY') == $key and $request->headers->get('X-API-VALUE') == $value) {
            $data =$this->getDoctrine()->getRepository('SettingToolBundle:GlobalOption')->apiAppDomain($id);
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
