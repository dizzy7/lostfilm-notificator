<?php

namespace AppBundle\Command;

use AppBundle\Repository\OptionRepository;
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
        $grabber = $this->getContainer()->get('app.http_grabber');
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        try {
            $rssFeed = $grabber->getPage('/rssdd.xml');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $rss = simplexml_load_string($rssFeed);

        $lastRssBuildDate = \DateTime::createFromFormat(\DateTime::RSS, (string) $rss->channel->lastBuildDate);
        /** @var OptionRepository $optionRepository */
        $optionRepository = $dm->getRepository('AppBundle:Option');
        $lastUpdate = $optionRepository->getLastUpdateDate();
        if ($lastRssBuildDate < $lastUpdate) {
            return 0;
        }

        return 0;
    }
}
