<?php

namespace AppBundle\Command;

use AppBundle\Service\Grabber\GrabberInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateEpisodesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:update:episodes')
            ->setDescription('Обновление данных о сериалах');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $grabberServices = $this->getContainer()->getParameter('grabbers');

        foreach ($grabberServices as $grabberService) {
            /** @var GrabberInterface $grabber */
            $grabber = $this->getContainer()->get($grabberService);
            $grabber->updateEpisodes();
        }

        $sender = $this->getContainer()->get('app.sender');
        $sender->sendNewEpisodeNotifications();

        return 0;
    }
}
