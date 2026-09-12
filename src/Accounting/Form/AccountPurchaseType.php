<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\Medicine\Entity\MedicineVendor;
use App\Inventory\Entity\Vendor;
use App\Accounting\Entity\AccountVendor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountPurchase;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class   AccountPurchaseType extends AbstractType
{

    /* @var $global GlobalOption */

    public  $global;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->global = $options['global'];

        $builder

            ->add('payment',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'add payment amount removeZero'],
                'constraints' =>[
                    new NotBlank(message: 'Please add payment amount')

            ]])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a transaction method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])
	        ->add('processType', ChoiceType::class, [
		        'attr'=>['class'=>'span12 m-wrap'],
		        'expanded'      =>false,
		        'multiple'      =>false,
		        'choices' => ['Payment of Due' => 'Due', 'Payment of Advance' => 'Advance', 'Debit Adjustment' => 'Debit', 'Credit Adjustment' => 'Credit'],
	        ])
            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->global->getId())
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
	                    ->andWhere("b.globalOption =".$this->global->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'']])
        ;
        if($this->global->getMainApp()->getSlug() == 'miss'){

            $builder->add('medicineVendor', EntityType::class, [
                'required'    => true,
                'class' => MedicineVendor::class,
                'placeholder' => '---Choose a vendor company---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'span12 m-wrap select2 vendor-ledger-medicine '],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.medicineConfig =".$this->global->getMedicineConfig()->getId())
                    ->orderBy('e.companyName','ASC'),
            ]);

        }elseif($this->global->getMainApp()->getSlug() == 'inventory'){

            $builder->add('vendor', EntityType::class, [
                'required'    => true,
                'class' => Vendor::class,
                'placeholder' => '---Choose a vendor company---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'span12 select2 m-wrap vendor-ledger-inventory'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.inventoryConfig =".$this->global->getInventoryConfig()->getId())
                    ->orderBy('e.companyName','ASC'),
            ]);
        }else{
            $builder->add('accountVendor', EntityType::class, [
                'required'    => true,
                'class' => AccountVendor::class,
                'placeholder' => '---Choose a vendor company---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'span10 select2  vendor-ledger-business'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->global->getId())
                    ->orderBy('e.companyName','ASC'),
            ]);
        }

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'global' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => AccountPurchase::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchase';
    }

}
