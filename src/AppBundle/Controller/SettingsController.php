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
            $this->redirect($this->generateUrl('app_settings_index'));
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
    public function generateTelegramCodeAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        $telegramCode = mt_rand(1000000, 9999999);
        $user->setTelegramConfirmationCode($telegramCode);

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        while (true) {
            try {
                $dm->flush();

                break;
            } catch (\MongoDuplicateKeyException $e) {
                $telegramCode = mt_rand(1000000, 9999999);
                $user->setTelegramConfirmationCode($telegramCode);
            }
        }

        return new JsonResponse(['code' => $telegramCode]);
    }

    /**
     * @Route("/settings/checkTelegramRegistration", options={"expose"=true})
     */
    public function checkTelegramRegistrationAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse(['success' => (boolean) $user->getTelegramId()]);
    }
}
