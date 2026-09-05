<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Medicine\Entity\MedicineVendor;
use App\Medicine\Entity\MedicinePurchaseReturn;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicinePurchase;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseType extends AbstractType
{
    /** @var  HospitalConfig */

    public  $option;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->option = $options['option'];

        $builder

            ->add('medicineVendor', EntityType::class, [
                'required'    => true,
                'class' => MedicineVendor::class,
                'placeholder' => '---Choose a vendor/supplier ---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'m-wrap span12 medicineVendor select2 inputs'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.medicineConfig =".$this->option->getMedicineConfig()->getId())
                    ->orderBy('e.companyName','ASC'),
            ])

            ->add('medicinePurchaseReturn', EntityType::class, [
                'required'    => false,
                'class' => MedicinePurchaseReturn::class,
                'placeholder' => '--Adjustment return--',
                'choice_label' => 'invoiceAmount',
                'attr'=>['class'=>'m-wrap span12 inputs'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->where("wt.medicineConfig =".$this->option->getMedicineConfig()->getId())
                    ->andWhere("wt.process ='approved'")
                    ->andWhere("wt.adjusted != 1"),
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'placeholder' => '---Choose a Transaction---',
                'attr'=>['class'=>'m-wrap span12 inputs transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id","ASC")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->option->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile banking---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->option->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('memo',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs ','required' => false ,'label' => 'form.name','placeholder'=>'Memo no']])
          //  ->add('remark', TextareaType::class, array('attr'=>array('class'=>'m-wrap span12  resize ','rows'=>3,'required' => true ,'label' => 'form.name','placeholder'=>'Enter remark')))
            ->add('receiveDate',DateType::class, ['attr'=>['class'=>'m-wrap span12 inputs','placeholder'=>'Enter receive date']])
            ->add('payment',TextType::class, ['attr'=>['class'=>'numeric span12 inputs m-wrap remove-value','placeholder'=>'Payment amount']])
            ->add('discountCalculation',NumberType::class, ['attr'=>['class'=>'m-wrap span12 salesInput','placeholder'=>'Add payment discount','data-original-title'=>'Add payment discount','autocomplete'=>'off']])
            ->add('invoiceMode', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap invoice-mode span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Vendor Invoice' => 'invoice', 'Manual' => 'manual'],
            ])
            ->add('discountType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap discount-type span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Percentage' => 'percentage', 'Flat' => 'flat'],
            ])
           // ->add('asInvestment')
            ->add('discount',HiddenType::class);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'option' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => MedicinePurchase::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'medicinepurchase';
    }
}
