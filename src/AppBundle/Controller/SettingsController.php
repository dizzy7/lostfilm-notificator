<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends Controller
{
    /**
     * @Route("/settings")
     * @Security("has_role('ROLE_USER')")
     */
    public function indexAction()
    {
        return new Response();
    }
}
