<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Accounting\Entity\VoucherType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountJournal;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class JournalVoucherType extends AbstractType
{



    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('voucherNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Voucher no'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter voucher no')
                ]])
            ->add('voucherType', EntityType::class, [
                'required'    => true,
                'class' => VoucherType::class,
                'placeholder' => '---Choose a voucher type---',
                'constraints' =>[new NotBlank(message: 'Please select to voucher type')],
                'choice_label' => 'voucherName',
                'attr'=>['class'=>'span12 m-wrap voucherType select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id")
            ]) ;

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountJournal::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'voucher';
    }


}
