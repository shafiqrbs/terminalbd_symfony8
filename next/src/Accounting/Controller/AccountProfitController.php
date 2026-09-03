<?php

namespace App\Accounting\Controller;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTime;
use App\Accounting\Entity\AccountProfit;
use Knp\Snappy\Pdf;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;


/**
 * AccountSalesAdjustment controller.
 *
 */
class AccountProfitController extends AbstractController
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

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function indexAction()
    {
        $em = $this->em;
        $data = $_REQUEST;
        $entities = $em->getRepository(\App\Accounting\Entity\AccountProfit::class)->findWithSearch( $this->getUser()->getGlobalOption(),$data);
        $pagination = $this->paginate($entities);
        return $this->render('Accounting/AccountProfit/index.html.twig', [
            'entities' => $pagination,
            'searchForm' => $data,
        ]);
    }


    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function newAction()
    {
        $search = $_REQUEST;
        $em = $this->em;
        $option = $this->getUser()->getGlobalOption();
        $datetime = new DateTime("now");
        $today = $datetime->format('d-m-Y');
        if(empty($search)){
            $date = date('Y-m-01', strtotime("-1 month -1 day", strtotime($today)));
            $month = date("m",strtotime($date));
            $year = date("Y",strtotime($date));
            $compare = new DateTime($date);
            $data =  $compare->format("Y-m-t 22:59:59");
        }else{
            $month = $search['month'];
            $year =  $search['year'];
            $compare = new DateTime("{$year}-{$month}-01");
            $data =  $compare->format("Y-m-t 22:59:59");
        }

        $entity = $this->em->getRepository(\App\Accounting\Entity\AccountProfit::class)->findOneBy(['globalOption' => $option,'month' => $month ,'year' => $year]);
      //  $overview = $this->em->getRepository(\App\Accounting\Entity\AccountProfit::class)->reportMonthlyProfitLoss($entity,$data);
      //  $capital = $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->getCapitalInvestment($option,$entity);

        if(empty($entity)){
            $entity = $this->em->getRepository(\App\Accounting\Entity\AccountProfit::class)->insertAccountProfit($option,$month,$year,$data);
            $overview = $this->em->getRepository(\App\Accounting\Entity\AccountProfit::class)->reportMonthlyProfitLoss($entity,$data);
            $sales = round($overview['sales'] + $overview['salesAdjustment']);
            $purchase = round($overview['purchase'] + $overview['purchaseAdjustment']);
            $expenditure = round ($overview['expenditure']);
            $revenue = round ($overview['operatingRevenue']);
            $salesReturn = round ($overview['salesReturn']);
            $profit = ($sales + $revenue) - ($purchase + $expenditure);
            $entity->setSales($sales);
            $entity->setPurchase($purchase);
            $entity->setExpenditure($expenditure);
            $entity->setRevenue($revenue);
            $entity->setSalesReturn($salesReturn);
            $entity->setMonth($month);
            $entity->setYear($year);
            if($profit > 0 ){
                $entity->setProfit($profit);
            }else{
                $entity->setLoss(abs($profit));
            }
            $em->persist($entity);
            $em->flush();
            if($entity->getLoss() > 0 or $entity->getProfit() > 0){
                $this->em->getRepository(\App\Accounting\Entity\Transaction::class)->getCapitalInvestment($option,$entity);
            }
            $this->requestStack->getSession()->getFlashBag()->add(
                'success',"Data has been added successfully"
            );
        }else{
            $this->requestStack->getSession()->getFlashBag()->add(
                'notice',"Already generated this {$month},{$year}"
            );
        }
        return $this->redirect($this->generateUrl('account_profit'));
    }

    #[IsGranted(new Expression("is_granted('ROLE_DOMAIN_ACCOUNTING_JOURNAL') or is_granted('ROLE_DOMAIN')"))]

    public function deleteAction(AccountProfit $entity)
    {
        $em = $this->em;
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AccountJournal entity.');
        }
        $em->remove($entity);
        $em->flush();
        return new Response('success');
    }

    public function downloadProfitAction()
    {
        $data = $_REQUEST;
        $em = $this->em;
        $entities = $em->getRepository(\App\Accounting\Entity\AccountProfit::class)->findWithSearch( $this->getUser()->getGlobalOption(),$data);
        $html = $this->renderView(
            'Accounting/AccountProfit/profitPdf.html.twig', [
                'option' => $this->getUser()->getGlobalOption(),
                'entities' => $entities,
                'searchForm' => $data,
            ]
        );
        $this->downloadPdf($html,'account-profit.pdf');
    }

    public function downloadPdf($html,$fileName = '')
    {
        $wkhtmltopdfPath = 'xvfb-run --server-args="-screen 0, 1280x1024x24" /usr/bin/wkhtmltopdf --use-xserver';
        $snappy          = new Pdf($wkhtmltopdfPath);
        $pdf             = $snappy->getOutputFromHtml($html);

        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename={$fileName}");
        echo $pdf;
        return new Response('');
    }



}
