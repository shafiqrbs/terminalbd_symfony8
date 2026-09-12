<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\Medicine\Entity\MedicineVendor;
use App\Inventory\Entity\Vendor;
use App\Accounting\Entity\AccountVendor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountPurchaseCommission;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountPurchaseCommissionType extends AbstractType
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

            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Add amount'],
                'constraints' =>[
                    new NotBlank(message: 'Please add amount')
                ]])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->global->getId()),
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile account---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->global->getId()),
            ])
        ;
        if($this->global->getMainApp()->getSlug() == 'miss'){

            $builder->add('medicineVendor', EntityType::class, [
                'required'    => true,
                'class' => MedicineVendor::class,
                'placeholder' => '---Choose a vendor company---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'span12 m-wrap vendor-ledger-medicine select2'],
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
                'attr'=>['class'=>'span12 select2 vendor-ledger-business'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
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
            'data_class' => AccountPurchaseCommission::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchaseCommission';
    }

}
