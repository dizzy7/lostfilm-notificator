<?php

namespace AppBundle\Command;

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
        $logger = $this->getContainer()->get('monolog.logger.applog');

        $grabber = $this->getContainer()->get('app.http_grabber');
        try {
            $rssFeed = $grabber->getPage('/rssdd.xml');
        } catch (\Exception $e) {
            $logger->error('Ошибка загрузки rss:'.$e->getMessage());

            return 1;
        }
        $rss = simplexml_load_string($rssFeed);

        $parser = $this->getContainer()->get('app.parser');
        $parser->parseRss($rss);

        $sender = $this->getContainer()->get('app.mail_sender');
        $sender->sendNewEpisodeNotifications();

        return 0;
    }
}
