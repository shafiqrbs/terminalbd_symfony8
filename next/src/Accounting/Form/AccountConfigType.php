<?php

namespace App\Accounting\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Accounting\Entity\AccountHead;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountingConfig;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AccountConfigType extends AbstractType
{

    /* @var $global GlobalOption */

    public  $global;

    public function __construct(GlobalOption $global)
    {
        $this->global = $global;

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('capitalInvestor', EntityType::class, [
                'class'     => AccountHead::class,
                'property'  => 'name',
                'empty_value' => '---Choose a capital investor---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'choice_translation_domain' => true,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.parent",'c')
                    ->where("e.status = 1")
                    ->andWhere("c.slug ='capital-investment'")
                    ->andWhere("e.globalOption =".$this->global->getId())
                    ->orderBy("e.name", "ASC")
            ])
            ->add('accountClose')
            ->add('salesReceiveSms')
            ->add('purchase')
            ->add('customPrint')
            ->add('borderColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span9 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('bodyFontSize', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'choices' => ['' => 'Font Size','10px' => '10px',  '11px' => '11px','12px' => '12px','13px' => '13px','14px' => '14px', '15px' => '15px', '16px' => '16px', '17px' => '17px','18px' => '18px',  '20px' => '20px', '22px' => '22px','24px' => '24px', '26px' => '26px',  '28px' => '28px','30px' => '39px','32px' => '32px','34px' => '34px','36px' => '36px', '38px' => '38px', '40px' => '40px','42px' => '42px',  '44px' => '44px', '46px' => '46px','48px' => '48px'],
            ])
            ->add('businessMode', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'choices' => ['Proprietorship' => 'Proprietorship',  'Partnership' => 'Partnership'],
            ])
            ->add('borderWidth',TextType::class,['attr'=>['class'=>'m-wrap numeric span8','maxLength'=>2]])
            ->add('address',TextareaType::class,['attr'=>['class'=>'m-wrap span12','row'=>5]])
            ->add('isPowered')
            ->add('isPrintHeader')
            ->add('isPrintFooter')
            ->add('invoiceWidth',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('invoiceHeight',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printLeftMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span8']])
            ->add('printTopMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span8']])
           ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountingConfig::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'config';
    }

}
