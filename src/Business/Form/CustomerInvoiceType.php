<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Tool\Entity\PaymentCard;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessInvoice;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerInvoiceType extends AbstractType
{
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder
            ->add('cardNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add payment information','data-original-title'=>'Add payment information','autocomplete'=>'off']])
            ->add('transactionId',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add TRX ID','data-original-title'=>'Add TRX ID','autocomplete'=>'off']])
            ->add('paymentMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add payment mobile no','data-original-title'=>'Add payment mobile no','autocomplete'=>'off']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 transactionMethod'],
                'placeholder' => '---Choose payment mode---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash'")
                    ->orderBy("e.id","ASC")
            ])
            ->add('paymentCard', EntityType::class, [
                'required'    => false,
                'choice_label' => 'name',
                'class' => PaymentCard::class,
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Choose payment card---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])
            ->add('accountBank', EntityType::class, [
                'required'    => false,
                'class' => AccountBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Choose receive bank account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])

            ->add('accountMobileBank', EntityType::class, [
                'required'    => false,
                'class' => AccountMobileBank::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Choose receive mobile account---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC")
            ])
	        ->add('comment',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 customer-input','rows'=>2,'placeholder'=>'Enter remarks']])

        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => BusinessInvoice::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'businessInvoice';
    }

}
