<?php

namespace App\Hospital\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\Service;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('vatRegNo',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter vat registration no']])
            ->add('customerPrefix',TextType::class, ['attr'=>['class'=>'m-wrap span12','maxlength'=> 4,'placeholder'=>'max 4 char']])
            ->add('invoicePrefix',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','maxlength'=> 4,'placeholder'=>'max 4 char']])
            ->add('vatPercentage',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span5','max'=> 100]])
            ->add('vatEnable')
            ->add('isMarketingExecutive')
            ->add('initialDiagnosticShow')
            ->add('services', EntityType::class, [
                'required'    => false,
                'expanded'    => true,
                'multiple'    => true,
                'choice_label' => 'name',
                'attr'=>['class'=>'check-list m-wrap'],
                'class' => Service::class,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.status =1')
                    ->orderBy("e.name","ASC")

            ])
            ->add('invoiceProcess',
                ChoiceType::class, [
                    'attr'=>['class'=>'check-list  span8'],
                    'choices' => ['Diagnostic' => 'diagnostic', 'Admission' => 'admission', 'Visit' => 'visit', 'Commission ' => 'commission'],
                    'required'    => true,
                    'multiple'    => true,
                    'expanded'  => true,
                    'empty_data'  => null,
            ])
            ->add('prescriptionBuilder')
            ->add('commissionAutoApproved')
            ->add('advanceSearchParticular')
            ->add('customPrint')
            ->add('invoicePrintLogo')
            ->add('isInvoiceTitle')
            ->add('isPrintHeader')
            ->add('isPrintReportHeader')
            ->add('isInventory')
            ->add('isPrintFooter')
            ->add('headerFile')
            ->add('footerFile')
            ->add('appointmentPrescription')
            ->add('printInstruction')
            ->add('printOff')
            ->add('address',TextareaType::class,['attr'=>['class'=>'m-wrap span12','rows'=>5]])
            ->add('messageAdmission',TextareaType::class,['attr'=>['class'=>'m-wrap span12 editor','rows'=>8, 'placeholder'=>'Enter message for admission']])
            ->add('messageDiagnostic',TextareaType::class,['attr'=>['class'=>'m-wrap span12 editor','rows'=>8, 'placeholder'=>'Enter message for Diagonestic']])
            ->add('messageVisit',TextareaType::class,['attr'=>['class'=>'m-wrap span12 editor','rows'=>8, 'placeholder'=>'Enter message for visit']])
            ->add('cssContent',TextareaType::class,['attr'=>['class'=>'m-wrap span12','rows'=>8, 'placeholder'=>'Enter Print custom print']])
            ->add('invoiceHeight',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printLeftMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printTopMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printMarginReportLeft',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('reportHeight',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printMarginReportTop',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printMarginReportBottom',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HospitalConfig::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'config';
    }

    /**
     * @return mixed
     */
    protected function PathologyChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 2 /** Pathology */ );

    }
    /**
     * @return mixed
     */
    protected function DepartmentChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 7 /** Department */);

    }

}
