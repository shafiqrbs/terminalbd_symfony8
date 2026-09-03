<?php

namespace App\Appearance\Form;

use App\Product\Entity\Category;
use App\Product\Entity\CategoryRepository;
use App\Tool\Entity\GlobalOption;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SidebarWidgetType extends AbstractType
{

    private $globalOption;

    public function __construct(GlobalOption $globalOption)
    {
        $this->globalOption = $globalOption;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into SidebarWidgetPanelType::configureOptions() and pass them as form options: $this->globalOption
        $builder->add('sidebarWidgetPanels', new SidebarWidgetPanelType($this->globalOption));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Appearance\Entity\SidebarWidget'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'setting_bundle_appearancebundle_featurewidget';
    }


}
