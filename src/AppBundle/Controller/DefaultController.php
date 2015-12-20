<?php

namespace AppBundle\Controller;

use AppBundle\Document\AbstractShow;
use AppBundle\Document\User;
use AppBundle\Form\Type\FeedbackType;
use AppBundle\Repository\AbstractShowRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Route("/", name="fos_user_profile_show")
     * @Security("has_role('ROLE_USER')")
     */
    public function indexAction()
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        /** @var AbstractShowRepository $showRepository */
        $showRepository = $dm->getRepository('AppBundle:AbstractShow');
        $shows = $showRepository->findActiveShows();

        return $this->render(
            'default/index.html.twig',
            [
                'shows' => $shows
            ]
        );
    }

    /**
     * @Route("/subscribe/{id}/{action}", options={"expose"=true})
     * @Security("has_role('ROLE_USER')")
     * @Method("POST")
     */
    public function toggleSubscribeAction($id, $action)
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $show = $dm->find('AppBundle:AbstractShow', $id);

        if ($show === null) {
            $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($action) {
            if (!$user->getSubscribedShows()->contains($show)) {
                $user->addSubscribedShow($show);
            }
        } else {
            if ($user->getSubscribedShows()->contains($show)) {
                $user->removeSubscribedShow($show);
            }
        }

        $dm->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/feedback")
     */
    public function feedbackAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(new FeedbackType($user));
        $form->handleRequest($request);

        $sended = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $mailer = $this->get('mailer');
            /** @var \Swift_Message $message */
            $message = $mailer->createMessage();
            $message->setSubject('Обратная связь с сайта lf.dizzy.name');
            $message->setTo($this->getParameter('feedback_email'));
            $message->setFrom($this->getParameter('from_email'));
            $body = 'Пользователь: ' . ($user ? $user->getEmail() : $form->get('email')->getData()) . "\n";
            $body .= $form->get('text')->getData();
            $message->setBody($body);

            $mailer->send($message);
            $sended = true;
        }

        return $this->render(
            'default/feedback.html.twig',
            [
                'form' => $form->createView(),
                'sended' => $sended
            ]
        );
    }
}
