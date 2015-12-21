<?php

namespace AppBundle\Twig;

class Extension extends \Twig_Extension
{
    public function getName()
    {
        return 'app_extension';
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('instanceof', function ($object, $class) {
                return $object instanceof $class;
            }),
        ];
    }
}
