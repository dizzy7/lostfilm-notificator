<?php


namespace AppBundle\Form\Type\User;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->remove('');
    }

    public function getName()
    {
        return 'fos_user_profile';
    }

    public function getParent()
    {
        return 'fos_user_profile';
    }
}