<?php

namespace App\Customer\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Business\Entity\BusinessInvoice;
use App\Business\Form\CustomerInvoiceType;
use App\Tool\Entity\Module;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BusinessController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }



    public function paginate($entities)
    {
        $paginator = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    public function indexAction($shop)
    {
        
        $user = $this->getUser();
        if(!empty($shop)){
            $globalOption = $this->em->getRepository(\App\Tool\Entity\GlobalOption::class)->findOneBy(array('slug' => $shop));
        }else{
            $globalOption ='';
        }
        $businessModel =  $globalOption->getBusinessConfig()->getBusinessModel();
        if($businessModel == "association"){
            $config = $globalOption->getBusinessConfig();
            $particular = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->findOneBy(array('businessConfig'=> $config,'slug'=>'registration-fee'));
            $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('user' => $this->getUser()->getId()));
            $entity = new BusinessInvoice();
            $editForm = $this->createCreateForm($entity);
            $Exinvoice = $this->em->getRepository(\App\Business\Entity\BusinessInvoice::class)->getLastInvoice($globalOption->getBusinessConfig(),$customer);
            $invoiceCheck = empty($Exinvoice) ? 'false' : "true";
            $template =  "Member";
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Data has been inserted successfully"
            );
            return $this->render("CustomerBundle:{$template}:dashboard.html.twig", array(
                'user'         => $user,
                'globalOption' => $globalOption,
                'customer' => $customer,
                'particular' => $particular,
                'entity' => $entity,
                'invoiceCheck' => $invoiceCheck,
                'form' => $editForm->createView(),
            ));
        }else{
            $template =  "Customer";
            return $this->render("CustomerBundle:{$template}:dashboard.html.twig", array(
                'user'         => $user,
                'globalOption' => $globalOption,
            ));
        }

    }



    /**
     * Creates a form to edit a Invoice entity.wq
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(BusinessInvoice $entity)
    {
        $globalOption = $this->getUser()->getGlobalOption();

        $form = $this->createForm(CustomerInvoiceType::class, $entity, array('action' => $this->generateUrl('student_invoice_create', array('shop' => $globalOption->getSlug())),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'id' => 'invoiceForm',
                'novalidate' => 'novalidate',
                'enctype' => 'multipart/form-data',

            ), 'globalOption' => $globalOption));
        return $form;
    }

    public function createAction(Request $request)
    {
        $data = $request->request->all();

        $user = $this->getUser();
        $config = $user->getGlobalOption()->getBusinessConfig();

        $particular = $this->em->getRepository(\App\Business\Entity\BusinessParticular::class)->findOneBy(array('businessConfig'=> $config,'slug'=>'registration-fee'));

        $entity = new BusinessInvoice();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        $customer = $this->em->getRepository(\App\DomainUser\Entity\Customer::class)->findOneBy(array('user'=>$user->getId()));
        $method = empty($entity->getTransactionMethod()) ? '' : $entity->getTransactionMethod()->getSlug();
        if (($form->isSubmitted() && $form->isValid() && $method == 'cash') ||
            ($form->isSubmitted() && $form->isValid() && $method == 'bank' && $entity->getAccountBank()) ||
            ($form->isSubmitted() && $form->isValid() && $method == 'mobile' && $entity->getAccountMobileBank())
        ) {

            $em = $this->em;
            $entity->setBusinessConfig($config);
            $entity->setCustomer($customer);
            $entity->setMobile($customer->getMobile());
            $entity->setReceived($particular->getSalesPrice());
            $entity->setDue(0);
            $entity->setProcess("In-progress");
            $entity->setEndDate(new \DateTime("now"));
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Data has been inserted successfully"
            );
            $this->em->getRepository(\App\Business\Entity\BusinessInvoiceParticular::class)->insertStudentInvoiceParticular($entity,$particular);
            $this->em->getRepository( \App\Business\Entity\BusinessInvoice::class )->updateInvoiceTotalPrice($entity);
            return $this->redirect($this->generateUrl('customer_business_dashboard', array('shop' => $user->getGlobalOption()->getSlug())));
        }else{
            $this->requestStack->getSession()->getFlashBag()->add(
                'warning', "Payment information does not valid"
            );
            return $this->redirect($this->generateUrl('customer_business_dashboard', array('shop' => $user->getGlobalOption()->getSlug())));
        }

    }

}
