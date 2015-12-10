<?php

namespace AppBundle\Command;

use AppBundle\Document\Episode;
use AppBundle\Document\Show;
use AppBundle\Repository\OptionRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateEpisodesCommand extends ContainerAwareCommand
{
    /** @var OutputInterface */
    private $output;

    protected function configure()
    {
        $this
            ->setName('app:update:episodes')
            ->setDescription('Обновление данных о сериалах');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $grabber = $this->getContainer()->get('app.http_grabber');
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        try {
            $rssFeed = $grabber->getPage('/rssdd.xml');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $rss = simplexml_load_string($rssFeed);

        $lastRssBuildDate = \DateTime::createFromFormat(\DateTime::RSS, (string)$rss->channel->lastBuildDate);
        /** @var OptionRepository $optionRepository */
        $optionRepository = $dm->getRepository('AppBundle:Option');
        $lastUpdate = $optionRepository->getLastUpdateDate();
        if ($lastRssBuildDate > $lastUpdate) {
            $this->parseEpisodes($rss);
        }

        return 0;
    }

    private function parseEpisodes(\SimpleXMLElement $rss)
    {
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $showRepository = $dm->getRepository('AppBundle:Show');

        $shows = $showRepository->findAll();
        foreach ($rss->channel->item as $item) {
            $title = (string)$item->title;
            $show = $this->findShowByEpisodeTitle($shows, $title);

            if ($show === null) {
                $this->output->writeln('<error>Сериал для эпизода не найден:</error> .' . $title);
                continue;
            }

            $this->addEpisodeIfNotExists($title, $show);
        }

        $dm->flush();
    }

    /**
     * @param $shows Show[]
     * @param $title
     * @return Show
     */
    private function findShowByEpisodeTitle($shows, $title)
    {
        // Сравниваем названия без учёта пробелов из-за проблем с форматированием на сайте и в rss
        $rssTitle = str_replace(' ', '', $title);
        foreach ($shows as $show) {
            $showTitle = str_replace(' ', '', $show->getTitle());
            if (mb_strpos($rssTitle, $showTitle, 0, 'UTF-8') === 0) {
                return $show;
            }
        }

        return null;
    }

    private function addEpisodeIfNotExists($title, Show $show)
    {
        //TODO сделать адекватный способ вырезать название сериала из строки
        $episodeTitle = 'TODO';

        $matches = [];
        if (preg_match('/\(S(\d+)E(\d+)\)/', $title, $matches)) {
            $seasonNumber = (int)$matches[1];
            $episodeNumber = (int)$matches[2];

            $episode = $show->getEpisodeByNumbers($seasonNumber, $episodeNumber);
            if ($episode !== null) {
                return false;
            }

            $episode = new Episode();
            $episode->setCreatedAt(new \DateTime());
            $episode->setTitle($episodeTitle);
            $episode->setSeasonNumber($seasonNumber);
            $episode->setEpisodeNumber($episodeNumber);
            //TODO создание массива ссылок на торрент-файлы эпизода
            //$episode->addLink();

            $show->addEpisode($episode);
            $this->output->writeln(
                sprintf(
                    '<info>Добавлен эпизод</info>: %s (S%dE%d)',
                    $show->getTitle(),
                    $seasonNumber,
                    $episodeNumber
                )
            );
        }
    }
}
