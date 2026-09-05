<?php

namespace App\Medicine\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Medicine\Entity\MedicinePurchaseReturn;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicinePurchase;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PurchaseManualType extends AbstractType
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
            ->add('memo',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs ','required' => false ,'label' => 'form.name','placeholder'=>'Memo no','autocomplete'=>'off']])
            ->add('receiveDate',DateType::class, ['attr'=>['class'=>'m-wrap span12 inputs','placeholder'=>'Enter receive date']])
            ->add('payment',TextType::class, ['attr'=>['class'=>'numeric span12 inputs m-wrap remove-value','placeholder'=>'Payment amount']]);
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
