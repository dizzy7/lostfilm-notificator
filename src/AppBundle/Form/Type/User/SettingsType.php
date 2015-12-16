<?php

namespace AppBundle\Form\Type\User;

use AppBundle\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'notificateVia',
            'choice',
            [
                'label' => 'Уведомлять через:',
                'choices' => [
                    User::NOTIFICATION_VIA_EMAIL => 'Email',
                    User::NOTIFICATION_VIA_TELEGRAM => 'Telegram',
                ],
                'expanded' => true,
            ]
        );
    }

    public function getName()
    {
        return 'app_user_settings';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );
    }
}
