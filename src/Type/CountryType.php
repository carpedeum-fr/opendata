<?php

namespace App\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CountryType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        \Locale::setDefault('en');

        $countries = array_flip(Intl::getRegionBundle()->getCountryNames());
        array_unshift($countries, ['FR' => Intl::getRegionBundle()->getCountryName('FR')]);

        $resolver->setDefaults(array(
            'choices' => $countries,
        ));
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}