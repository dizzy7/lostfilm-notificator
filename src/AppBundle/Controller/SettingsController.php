<?php

namespace AppBundle\Controller;

use AppBundle\Document\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("has_role('ROLE_USER')")
 */
class SettingsController extends Controller
{
    /**
     * @Route("/settings")
     */
    public function indexAction()
    {
        return $this->render('settings/index.html.twig', []);
    }

    /**
     * @Route("/settings/getTelegramCode", options={"expose"=true})
     */
    public function generateTelegramCode()
    {
        /** @var User $user */
        $user = $this->getUser();

        $telegramCode = mt_rand(100000, 999999);
        $user->setTelegramConfirmationCode($telegramCode);

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $dm->flush();

        return new JsonResponse(['code' => $telegramCode]);
    }
}
