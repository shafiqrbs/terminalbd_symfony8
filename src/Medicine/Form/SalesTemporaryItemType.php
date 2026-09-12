<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineSalesItem;
use App\Medicine\Repository\MedicineSalesItemRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SalesTemporaryItemType extends AbstractType
{
    protected $glodbalOption;
    protected $em;


    /** @var  $globalOption GlobalOption */
    private $globalOption;
    private $config;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->glodbalOption = $options['globalOption'];
        $this->em = $options['em'];
        // Derived by the Symfony 2 constructor; options replaced it and the derivation went too.
        $this->config = $options['globalOption']->getMedicineConfig();

        $builder

            ->add('stockName',TextType::class, ['attr'=>['class'=>'m-wrap span12 select2StockMedicine input','placeholder'=>'Enter stock medicine name']])
            ->add('itemPercent',TextType::class, ['attr'=>['class'=>'m-wrap span3 input number','autocomplete'=>'off','placeholder'=>'Percent (%)']])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span3 input number','autocomplete'=>'off','placeholder'=>'MRP']])
            ->add('quantity',TextType::class, ['attr'=>['class'=>'m-wrap span3 form-control input-number input number','autocomplete'=>'off','placeholder'=>'QTY']]);
            if($this->config->isPurchaseItem() == 1) {
                $builder->add('purchaseItem', ChoiceType::class, [
                    'attr' => ['class' => 'm-wrap span12 input'],
                    'expanded' => false,
                    'multiple' => false,
                    'placeholder' => '---Purchase Item---',
                    'choices' => [],
                ]);
            }
    }

    public function discountPercentList()
    {
        return $this->em->discountPercentList();

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'em' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => MedicineSalesItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'salesTemporaryItem';
    }
}
