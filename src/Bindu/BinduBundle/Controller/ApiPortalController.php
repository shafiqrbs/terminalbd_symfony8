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

    public function onboardAction(Request $request)
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
        $intro = array(
            'video_url' => "https://youtu.be/CPclGyYCGtY?si=12eJJnM7yUQvh7RU",
            'terms_condition'=> '<p>1. Acceptance of Terms
    By using this Point of Sale (POS) application, you agree to abide by these Terms and Conditions. If you do not agree, please refrain from using the application.
2. User Responsibilities
    Ensure that all transactions are legally compliant and accurately recorded.
    Maintain the confidentiality of login credentials and sensitive business data.
    Use the software only for its intended purpose—business transactions and sales management.
3. Licensing & Access
    This POS software is licensed, not sold. You are granted a non-transferable, non-exclusive right to use it.
    Unauthorized copying, distribution, or modification of the software is strictly prohibited.
4. Data Privacy & Security
    The application may collect and store transaction data for business analytics and operational purposes.
    Users are responsible for securing their device and preventing unauthorized access.
    The app provider is not liable for any data loss due to user negligence.
5. Payment & Subscriptions
    If applicable, subscription plans must be paid on time to maintain uninterrupted service.
    Failure to renew may result in restricted access to certain features.
6. Software Updates & Maintenance
    The provider reserves the right to update the software periodically to improve performance and security.
    Users must keep their software up to date to ensure compatibility and security.
7. Prohibited Activities
    Users must not:
    Engage in fraudulent transactions or manipulate sales records.
    Reverse-engineer, modify, or attempt to hack the application.
    Use the POS app for illegal activities.
8. Limitation of Liability
    The app provider is not responsible for financial losses, transaction errors, or business disruptions caused by misuse or external factors.
    The software is provided "as is," without warranties of any kind.
9. Termination of Access
    The provider reserves the right to suspend or terminate access if users violate these terms.
    In case of termination, users must cease all use of the software immediately.
10. Governing Law
    These terms are governed by applicable local and international laws. Any disputes shall be resolved in accordance with the jurisdiction specified by the provider.
By using this POS software, you acknowledge and agree to these terms. If you have any questions, contact customer support.</p>',
            'aboutus'=> '<p>Founded in 2018, POSKeeper Global IT began its journey with a mission to revolutionize the pharmaceutical retail sector by offering dedicated and reliable Point of Sale (POS) solutions for medicine and healthcare businesses.
We specialize in providing tailored POS systems designed specifically to address the unique needs of pharmacies and medical retailers. Our solutions streamline inventory management, enable precise billing, and ensure compliance with regulatory requirements, making it easier for businesses to focus on what truly matters—serving their customers and patients.
Since our inception, we have expanded to serve a broader range of industries, while maintaining our roots in the healthcare sector. With a strong emphasis on innovation, security, and ease of use, we continue to empower businesses with tools that simplify operations, improve customer experiences, and drive growth.
Today, we are proud to support a diverse portfolio of businesses, from independent pharmacies to large-scale medical chains, with state-of-the-art hardware, intuitive software, and 24/7 customer support.
Your growth is our priority, and every feature we create is designed to make your business thrive.<p/>',
            'onboard'=> 1,
            'demo' => $array,
        );
        return $intro;
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
