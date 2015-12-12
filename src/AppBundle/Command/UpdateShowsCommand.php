<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateShowsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:update:shows')
            ->setDescription('Обновление данных о сериалах');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $grabber = $this->getContainer()->get('app.http_grabber');
        $logger = $this->getContainer()->get('monolog.logger.applog');

        try {
            $showsPage = iconv('windows-1251', 'utf-8', $grabber->getPage('/serials.php'));
        } catch (\Exception $e) {
            $logger->error('Ошибка загрузки страницы с сериалами: ' . $e->getMessage());

            return 1;
        }

        $parser = $this->getContainer()->get('app.parser');
        $parser->updateShows($showsPage);

        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $shows = $dm->getRepository('AppBundle:Show')->findActiveShows();

        foreach ($shows as $show) {
            try {
                $showPage = iconv('windows-1251', 'utf-8', $grabber->getPage($show->getUrl()));
                $parser->updateShowStatus($show, $showPage);

                sleep(mt_rand(1, 5));
            } catch (\Exception $e) {
                $logger->error('Ошибка загрузки страницы сериала ' . $show->getTitle() . ':' . $e->getMessage());
                sleep(60);
            }
        }

        return 0;
    }
}
