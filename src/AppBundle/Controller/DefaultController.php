<?php

namespace AppBundle\Controller;

use AppBundle\Form\Type\SubscriptionType;
use AppBundle\Repository\AbstractShowRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Route("/", name="fos_user_profile_show")
     * @Security("has_role('ROLE_USER')")
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $user = $this->getUser();
        $form = $this->createForm(new SubscriptionType(), $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->flush();

            return $this->redirect($this->generateUrl('app_default_index'));
        }

        /** @var AbstractShowRepository $showRepository */
        $showRepository = $dm->getRepository('AppBundle:AbstractShow');
        $shows = $showRepository->findActiveShows();
        $indexedShows = [];
        foreach ($shows as $show) {
            $indexedShows[$show->getId()] = $show;
        }

        return $this->render(
            'default/index.html.twig',
            [
                'form' => $form->createView(),
                'shows' => $indexedShows
            ]
        );
    }
}
