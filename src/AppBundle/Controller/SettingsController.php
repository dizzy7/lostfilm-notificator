<?php

namespace AppBundle\Controller;

use AppBundle\Document\User;
use AppBundle\Form\Type\User\SettingsType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("has_role('ROLE_USER')")
 */
class SettingsController extends Controller
{
    /**
     * @Route("/settings")
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        $form = $this->createForm(new SettingsType(), $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $dm->flush($user);
        }

        return $this->render(
            'settings/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
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

    /**
     * @Route("/settings/checkTelegramRegistration", options={"expose"=true})
     */
    public function checkTelegramRegistration()
    {
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse(['success' => (boolean) $user->getTelegramId()]);
    }
}
