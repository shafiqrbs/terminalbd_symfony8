<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Business\Entity\BusinessParticular;
use App\Business\Entity\WearHouse;
use App\User\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessBatchParticular;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BusinessBatchParticularType extends AbstractType
{


    /** @var $config BusinessConfig */
    private $config;

    public function __construct(private readonly GlobalOption $globalOption)
    {
        $this->config     = $this->globalOption->getBusinessConfig();
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('startMeterReading',TextType::class, ['required'=> true,'attr'=>['class'=>'m-wrap span8 number','placeholder'=>'Start Meter Reading','autocomplete'=>'off']])
            ->add('businessParticular', EntityType::class, [
                'required'    => true,
                'class' => BusinessParticular::class,
                'empty_value' => '---Choose a item ---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap number'],
                'constraints' =>[ new NotBlank(message: 'Please select item name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.businessConfig =".$this->config->getId()),
            ])
            ->add('wearhouse', EntityType::class, [
                'required'    => true,
                'class' => WearHouse::class,
                'empty_value' => '---Choose a from wearhouse ---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.businessConfig =".$this->config->getId()),
            ])
            ->add('salesBy', EntityType::class, [
                'required'    => true,
                'class' => User::class,
                'property' => 'userFullName',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')
                    ->where("u.isDelete != 1")
	                    ->andWhere("u.enabled = 1")
	                    ->andWhere("u.domainOwner = 2")
	                    ->andWhere("u.globalOption =".$this->globalOption->getId())
                    ->orderBy("u.username", "ASC")
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BusinessBatchParticular::class
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
