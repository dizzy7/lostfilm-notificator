<?php

namespace AppBundle\Form;

use AppBundle\Document\Show;
use AppBundle\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'subscribedShows',
            'document',
            [
                'class'=> Show::class,
                'expanded' => true,
                'multiple' => true
            ]
        );
    }

    public function getName()
    {
        return 'subscription';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}