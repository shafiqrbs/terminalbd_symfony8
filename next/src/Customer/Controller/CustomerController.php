<?php

namespace App\Customer\Controller;


use Frontend\FrontentBundle\Service\Cart;
use App\Tool\Entity\Module;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{


    public function paginate($entities)
    {
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities,
            $this->get('request')->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    public function indexAction($shop)
    {
        
        $user = $this->getUser();
        if(!empty($shop)){
            $globalOption = $this->getDoctrine()->getRepository(\App\Tool\Entity\GlobalOption::class)->findOneBy(array('slug' => $shop));
        }else{
            $globalOption ='';
        }
        $domainApp =  $globalOption->getMainApp();
        if($domainApp == "business"){
            return $this->redirect($this->generateUrl('customer_business_dashboard', array('shop' => $globalOption->getSlug())));
        }elseif($domainApp == "medicine") {
            return $this->redirect($this->generateUrl('customer_medicine_dashboard', array('shop' => $globalOption->getSlug())));
        }
        return $this->render("Customer/Customer/dashboard.html.twig", array(
            'user'         => $user,
            'globalOption' => $globalOption,
        ));

    }

    public function newAction()
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('user'=>$this->getUser()->getId()));
        $entity = new BusinessInvoice();
        $editForm = $this->createCreateForm($entity);
        $config = $this->getUser()->getGlobalOption()->getBusinessConfig();
        $outstanding = 0;
        return $this->render("Customer/Invoice/new.html.twig", array(
            'globalOption' => $this->getUser()->getGlobalOption(),
            'customer' => $customer,
            'entity' => $entity,
            'outstanding' => $outstanding,
            'form' => $editForm->createView(),
        ));

    }

    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(BusinessInvoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();
        $location = $this->getDoctrine()->getRepository(\App\Location\Entity\Location::class);
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into CustomerInvoiceType::configureOptions() and pass them as form options: $globalOption,$location), $entity, array(
        $form = $this->createForm(new CustomerInvoiceType($globalOption,$location), $entity, array(
            'action' => $this->generateUrl('customerweb_invoice_create', array('shop' => $globalOption->getSlug())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',

            )
        ));
        return $form;
    }

    public function createAction(Request $request)
    {
        $data = $request->request->all();
        $config = $this->getUser()->getGlobalOption();
        $user = $this->getUser()->getId();
        $entity = new BusinessInvoice();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $customer = $this->getDoctrine()->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('user'=>$user));

        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if (($form->isValid() && $method == 'cash') ||
            ($form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {
            var_dump($data);
            exit;
            $em = $this->getDoctrine()->getManager();
            $entity->setBusinessConfig($config->getBusinessConfig());
            $entity->setCustomer($customer);
            $entity->setMobile($customer->getMobile());
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success', "Data has been inserted successfully"
            );
            $this->getDoctrine()->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertCustomerInvoiceParticular($entity, $data);
            $this->getDoctrine()->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($entity);
            return $this->redirect($this->generateUrl('customerweb_invoice', array('shop' => $config->getSlug())));
        }

    }



    public function customerDomainAction()
    {

        $user = $this->getUser();
        return $this->render('Customer/Customer/domain.html.twig', array(
            'user' => $user,
            'globalOption' => '',
        ));

    }
    
    public function moduleAction(Module $module)
    {

        $globalOption = $this->getUser()->getGlobalOption();
        $entities = $this->getDoctrine()->getRepository(\App\Content\Entity\Page::class)->findModuleContentList($globalOption,$module->getId());
        $pagination = $this->paginate($entities);
        return $this->render('Customer/Customer/module.html.twig', array(
            'globalOption' => $globalOption,
            'module' => $module,
            'pagination' => $pagination,
        ));

    }
    public function moduleDetailsAction()
    {
        $user = $this->getUser();
        return $this->render('Customer/Customer/domain.html.twig', array(
            'user' => $user,
            'globalOption' => '',
        ));

    }



}
