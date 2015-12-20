<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FeedbackType extends AbstractType
{
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->user === null) {
            $builder->add(
                'email',
                'email',
                [
                    'label' => 'Email'
                ]
            );
        }
        $builder->add(
            'text',
            'textarea',
            [
                'label' => 'Сообщение'
            ]
        );
    }
}