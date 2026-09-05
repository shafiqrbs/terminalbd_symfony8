<?php

namespace App\Hospital\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\HmsVendor;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\HmsPurchase;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccessoriesPurchaseType extends AbstractType
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

            ->add('vendor', EntityType::class, [
                'required'    => true,
                'class' => HmsVendor::class,
                'placeholder' => '---Choose a vendor ---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'span12'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->where("wt.status = 1")
                    ->andWhere("wt.mode = 'accessories'")
                    ->andWhere("wt.hospitalConfig =".$this->option->getHospitalConfig()->getId()),
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
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
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->option->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('memo',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','required' => true ,'label' => 'form.name','placeholder'=>'Memo no'],
                'constraints' =>[
                    new NotBlank(message: 'Please add  memo no')
            ]])
            ->add('remark',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 resize ','rows'=>3,'required' => true ,'label' => 'form.name','placeholder'=>'Enter remark']])
            ->add('receiveDate',TextType::class, ['attr'=>['class'=>'m-wrap span12 dateCalendar','placeholder'=>'Enter receive date']])
            ->add('payment',TextType::class, ['attr'=>['class'=>'numeric m-wrap span12','placeholder'=>'Payment amount']
            ]);
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
            'data_class' => HmsPurchase::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_hmspurchase';
    }
}
