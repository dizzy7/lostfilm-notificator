<?php

namespace AppBundle\Service;

use AppBundle\Document\Episode;
use AppBundle\Document\Show;
use AppBundle\Repository\OptionRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

class RssParser
{
    private $dm;
    private $logger;

    public function __construct(DocumentManager $dm, LoggerInterface $logger)
    {
        $this->dm = $dm;
        $this->logger = $logger;
    }

    public function parseRss(\SimpleXMLElement $rss)
    {
        $lastRssBuildDate = \DateTime::createFromFormat(\DateTime::RSS, (string)$rss->channel->lastBuildDate);
        /** @var OptionRepository $optionRepository */
        $optionRepository = $this->dm->getRepository('AppBundle:Option');
        $lastUpdate = $optionRepository->getLastUpdateDate();
        if ($lastRssBuildDate > $lastUpdate) {
            $this->parseEpisodes($rss);
        }

        return 0;
    }

    private function parseEpisodes(\SimpleXMLElement $rss)
    {
        $showRepository = $this->dm->getRepository('AppBundle:Show');

        $shows = $showRepository->findAll();
        foreach ($rss->channel->item as $item) {
            $title = (string)$item->title;
            $show = $this->findShowByEpisodeTitle($shows, $title);

            if ($show === null) {
                $this->logger->alert('Сериал для эпизода не найден:' . $title);
                continue;
            }

            $this->addEpisodeIfNotExists($title, $show);
        }

        $this->dm->flush();
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
            $this->logger->info(
                sprintf(
                    'Добавлен эпизод: %s (S%dE%d)',
                    $show->getTitle(),
                    $seasonNumber,
                    $episodeNumber
                )
            );
        }
    }
}