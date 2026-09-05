<?php

namespace App\Business\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Accounting\Entity\AccountVendor;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessPurchase;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
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

            ->add('vendor', EntityType::class, [
                'required'    => true,
                'class' => AccountVendor::class,
                'placeholder' => '---Choose a vendor ---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->option->getId()),
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'placeholder' => '---Choose a Transaction---',
                'attr'=>['class'=>'span12 transactionMethod'],
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

            ->add('discountCalculation',NumberType::class, ['attr'=>['class'=>'m-wrap span12 salesInput','placeholder'=>'Add payment discount','data-original-title'=>'Add payment discount','autocomplete'=>'off']])
            ->add('discountType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap discount-type span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Percentage' => 'percentage', 'Flat' => 'flat'],
            ])
            ->add('file', FileType::class,[
                'required' => false,
                'attr'=>['class'=>'custom-file-input'],
                'constraints' => [new File(maxSize: '1024k', mimeTypes: ['application/pdf',
                    'application/x-pdf',
                    'image/png',
                    'image/jpeg',
                    'image/jpg',
                    'image/gif'], mimeTypesMessage: 'Please upload a valid document')],
            ])
            ->add('memo',TextType::class, ['attr'=>['class'=>'m-wrap span12','required' => true ,'label' => 'form.name','placeholder'=>'Memo no']])
            ->add('receiveDate',DateType::class, ['attr'=>['class'=>'m-wrap span12 inputs','placeholder'=>'Enter receive date']])
            ->add('payment',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Payment amount','autocomplete'=>'off']])
            ->add('remark',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> 5,'placeholder'=>'Enter narration','autocomplete'=>'off']
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
            'data_class' => BusinessPurchase::class
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
