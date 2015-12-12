<?php

namespace AppBundle\Form\Type;

use AppBundle\Document\Show;
use AppBundle\Document\User;
use AppBundle\Repository\ShowRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'subscribedShows',
            'document',
            [
                'class' => Show::class,
                'expanded' => true,
                'multiple' => true,
                'query_builder' => function (ShowRepository $repository) {
                    return $repository->findActiveShowsQueryBuilder();
                },
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
            'data_class' => User::class,
        ]);
    }
}