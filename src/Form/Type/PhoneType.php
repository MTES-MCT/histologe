<?php

namespace App\Form\Type;

use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $supportedRegions = $phoneNumberUtil->getSupportedRegions();
        $countryCallingCodes = [];
        foreach ($supportedRegions as $region) {
            $country = \Locale::getDisplayRegion('-'.$region, 'en');
            $label = $country.' (+'.$phoneNumberUtil->getCountryCodeForRegion($region).')';
            $countryCallingCodes['+'.$phoneNumberUtil->getCountryCodeForRegion($region)] = $label;
        }
        array_multisort($countryCallingCodes);

        $resolver->setDefaults([
            'expanded' => true,
            'multiple' => true,
            'attr' => ['class' => 'phone-input'],
            'label' => 'Téléphone',
            'help' => 'Format attendu : Veuillez sélectionner le pays pour obtenir l\'indicatif téléphonique, puis saisir le numéro de téléphone au format national (sans l\'indicatif). Exemple pour la France : 0702030405.',
            'countryCodes' => $countryCallingCodes,
        ]);
    }

    public function getParent(): string
    {
        return TelType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['countryCodes'] = $options['countryCodes'];
        $view->vars['selectedCode'] = '+33';
        $view->vars['inputNumber'] = '';

        $phoneNumberStr = $form->getNormData();
        if (!empty($phoneNumberStr)) {
            $phoneNumberUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneNumber = $phoneNumberUtil->parse($phoneNumberStr);
                if ($phoneNumberUtil->isPossibleNumber($phoneNumber)) {
                    $view->vars['selectedCode'] = '+'.$phoneNumber->getCountryCode();
                    $view->vars['inputNumber'] = str_pad('', $phoneNumber->getNumberOfLeadingZeros(), '0');
                    $view->vars['inputNumber'] .= $phoneNumber->getNationalNumber();
                }
            } catch (\Exception $e) {
                return;
            }
        }
    }
}
