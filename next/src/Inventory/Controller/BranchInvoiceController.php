<?php

namespace App\Inventory\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use App\Inventory\Form\BranchInvoiceItemType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use App\Inventory\Entity\BranchInvoiceItem;
use Symfony\Component\HttpFoundation\Request;
use App\Inventory\Entity\BranchInvoice;
use Symfony\Component\HttpFoundation\Response;
use Knp\Snappy\Pdf;
use CodeItNow\BarcodeBundle\Utils\BarcodeGenerator;
/**
 * BranchInvoice controller.
 *
 */
class BranchInvoiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly PaginatorInterface $paginator,
    ) {
    }


    public function paginate($entities)
    {

        $paginator  = $this->paginator;
        $pagination = $paginator->paginate(
            $entities,
            $this->requestStack->getCurrentRequest()->query->get('page', 1)/*page number*/,
            25  /*limit per page*/
        );
        return $pagination;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function indexAction()
    {



        $em = $this->em;
        $data = $_REQUEST;
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $entities = $em->getRepository(\App\Inventory\Entity\BranchInvoice::class)->invoiceLists($inventory,$data);
        $pagination = $this->paginate($entities);
        $branches = $em->getRepository(\App\DomainUser\Entity\Branches::class)->findBy(['globalOption'=>$this->getUser()->getGlobalOption()]);
        return $this->render('Inventory/BranchInvoice/index.html.twig', [
            'entities' => $pagination,
            'branches' => $branches,
            'searchForm' => $data,
        ]);
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function searchAction(Request $request)
    {
        $em = $this->em;
        $sales = $request->request->get('sales');
        $barcode = $request->request->get('barcode');
        $sales = $em->getRepository(\App\Inventory\Entity\BranchInvoice::class)->find($sales);
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $purchaseItem = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->returnPurchaseItemDetails($inventory,$barcode);
        $checkQuantity = $this->em->getRepository(\App\Inventory\Entity\BranchInvoiceItem::class)->checkPurchaseQuantity($purchaseItem);
        $itemDetails= '';
        $salesItems = '';
        $salesTotal = '';
        if($purchaseItem && $checkQuantity == true ){
            //$itemDetails = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findBarcode($purchaseItem);
            $this->em->getRepository(\App\Inventory\Entity\BranchInvoiceItem::class)->insertBranchInvoiceItems($sales,$purchaseItem);
            $salesTotal = $this->em->getRepository(\App\Inventory\Entity\BranchInvoice::class)->updateBranchInvoiceTotalPrice($sales);
            $salesItems = $em->getRepository(\App\Inventory\Entity\BranchInvoiceItem::class)->getBranchInvoiceItems($sales);
        }else{
            $salesTotal = $this->em->getRepository(\App\Inventory\Entity\BranchInvoice::class)->updateBranchInvoiceTotalPrice($sales);
            $salesItems = $em->getRepository(\App\Inventory\Entity\BranchInvoiceItem::class)->getBranchInvoiceItems($sales);
        }

        //return new Response($salesItems);
        return new Response(json_encode(['salesTotal'=>$salesTotal,'purchaseItem' => $itemDetails ,'salesItem'=>$salesItems]));
        exit;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function salesItemUpdateAction(Request $request)
    {
        $em = $this->em;
        $salesItemId = $request->request->get('salesItemId');
        $quantity = $request->request->get('quantity');
        $salesPrice = $request->request->get('salesPrice');
        $customPrice = $request->request->get('customPrice');

        $salesItem = $em->getRepository(\App\Inventory\Entity\BranchInvoiceItem::class)->find($salesItemId);
        $salesItem->setQuantity($quantity);
        $salesItem->setBranchInvoicePrice($salesPrice);
        if (!empty($customPrice)){
            $salesItem->setCustomPrice(1);
        }
        $salesItem->setSubTotal($quantity * $salesPrice);
        $em->persist($salesItem);
        $em->flush();
        $salesTotal = $this->em->getRepository(\App\Inventory\Entity\BranchInvoice::class)->updateBranchInvoiceTotalPrice($salesItem->getBranchInvoice());
        return new Response(json_encode(['salesTotal'=>$salesTotal]));
        exit;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function newAction()
    {
        $branch = $_REQUEST['branch'];

        $em = $this->em;
        $branches = $em->getRepository(\App\DomainUser\Entity\Branches::class)->find($branch);
        if(!empty($branches)){
            $entity = new BranchInvoice();
            $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
            $entity->setInventoryConfig($inventory);
            $entity->setCreatedBy($this->getUser());
            $entity->setBranches($branches);
            $em->persist($entity);
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Invoice has been generated successfully, add your item"
            );
            return $this->redirect($this->generateUrl('inventory_branchinvoice_edit', ['code' => $entity->getInvoice()]));
        }else{
            $this->requestStack->getSession()->getFlashBag()->add(
                'error', "Invoice has not been generated, please select branch "
            );
            return $this->redirect($this->generateUrl('inventory_branch'));

        }


    }

    /**
     * Finds and displays a BranchInvoice entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->em;

        $entity = $em->getRepository(\App\Inventory\Entity\BranchInvoice::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BranchInvoice entity.');
        }
        return $this->render('Inventory/BranchInvoice/show.html.twig', [
            'entity'      => $entity,
        ]);
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function editAction($code)
    {
        $em = $this->em;
        $entity = $em->getRepository(\App\Inventory\Entity\BranchInvoice::class)->findOneBy(['invoice'=>$code]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BranchInvoice entity.');
        }
        $branchItem = new BranchInvoiceItem();
        $form = $this->createEditForm($entity,$branchItem);
        return $this->render('Inventory/BranchInvoice/new.html.twig', [
            'entity'      => $entity,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Creates a form to edit a BranchInvoice entity.wq
     *
     * @param BranchInvoice $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(BranchInvoice $entity, BranchInvoiceItem $branchItem )
    {
        $form = $this->createForm(new BranchInvoiceItemType(), $branchItem, [
            'action' => $this->generateUrl('inventory_branchinvoice_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
            'attr' => [
                'class' => 'horizontal-form',
                'novalidate' => 'novalidate',
            ]
        ]);
        return $form;
    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function updateAction(Request $request, BranchInvoice $entity)
    {

        $em = $this->em;
        $branchItem = new BranchInvoiceItem();
        $form = $this->createEditForm($entity,$branchItem);
        $form->handleRequest($request);

        $data = $request->request->all();
        $barcode = $data["appstore_bundle_inventorybundle_branchInvoiceItem"]["barcode"];
        $quantity = $data["appstore_bundle_inventorybundle_branchInvoiceItem"]["quantity"];
        $purchaseItem = $em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->findOneBy(['barcode'=>$barcode]);
        if(!empty($purchaseItem) && $quantity > 0 ) {

            if ($form->isSubmitted() && $form->isValid()) {
                $branchItem->setBranchInvoice($entity);
                $branchItem->setPurchaseItem($purchaseItem);
                $branchItem->setSalesPrice($purchaseItem->getSalesPrice());
                $branchItem->setSubTotal($purchaseItem->getSalesPrice() * $branchItem->getQuantity());
                $em->persist($branchItem);
                $em->flush();
                $this->requestStack->getSession()->getFlashBag()->add(
                    'success', "Data has been added successfully"
                );
                $em->getRepository(\App\Inventory\Entity\BranchInvoice::class)->updateBranchInvoiceTotalPrice($entity);
                return $this->redirect($this->generateUrl('inventory_branchinvoice_edit', ['code' => $entity->getInvoice()]));
            }
        }

        $this->requestStack->getSession()->getFlashBag()->add('error', "Invalid barcode,please add correct barcode");
        return $this->render('Inventory/BranchInvoice/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView(),
        ]);

    }

    #[IsGranted('ROLE_DOMAIN_INVENTORY_APPROVE')]

    public function approveAction(BranchInvoice $entity)
    {
        if (!empty($entity)) {
            $em = $this->em;
            $entity->setProcess('approved');
            $entity->setApprovedBy($this->getUser());
            $em->flush();
            $this->requestStack->getSession()->getFlashBag()->add(
                'success', "Invoice has been approved successfully"
            );
        }
        return $this->redirect($this->generateUrl('inventory_branchinvoice'));


    }


    #[IsGranted('ROLE_DOMAIN_INVENTORY_SALES')]

    public function deleteAction(BranchInvoice $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BranchInvoice entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
        exit;
    }

    /**
     * Deletes a BranchInvoiceItem entity.
     *
     */
    public function itemDeleteAction(BranchInvoiceItem $entity)
    {

        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BranchInvoiceItem entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
        exit;

    }

    public function itemPurchaseDetailsAction(Request $request)
    {
        $item = $request->request->get('item');
        $inventory = $this->getUser()->getGlobalOption()->getInventoryConfig();
        $data = $this->em->getRepository(\App\Inventory\Entity\PurchaseItem::class)->itemPurchaseDetails($inventory,$item);
        return new Response($data);
    }

    public function pdfAction(BranchInvoice $entity)
    {

        $html = $this->renderView(
            'Inventory/BranchInvoice/pdf.html.twig', [
                'entity' => $entity,
                'print' => ''
            ]
        );
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoicePdf.pdf"');
        echo $pdf;

        return new Response('');
    }

    public function invoicePrintAction(BranchInvoice $entity)
    {
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find BranchInvoice entity.');
        }

        $barCoder = $this->barCoder($entity->getInvoice());
        return $this->render('Inventory/BranchInvoice/pdf.html.twig', [
            'entity'      => $entity,
            'barCoder'      => $barCoder,
            'print' => '<script>window.print();</script>'
        ]);
    }

    public function barCoder($barcoder){

        $barcode = new BarcodeGenerator();
        $barcode->setText($barcoder);
        $barcode->setType(BarcodeGenerator::Code128);
        $barcode->setScale(1);
        $barcode->setThickness(30);
        $barcode->setFontSize(8);
        $code = $barcode->generate();
        $data = '';
        $data .='<div class="barcode-block">';
        $data .='<div class="centered">';
        $data .='<img src="data:image/png;base64,'.$code.'" />';
        $data .='</div>';
        $data .='</div>';
        return $data;
    }



}
