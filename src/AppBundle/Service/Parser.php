<?php

namespace AppBundle\Service;

use AppBundle\Document\Episode;
use AppBundle\Document\Show;
use AppBundle\Repository\OptionRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Sunra\PhpSimple\HtmlDomParser;

class Parser
{
    private $dm;
    private $logger;
    private $domParser;

    public function __construct(DocumentManager $dm, LoggerInterface $logger, HtmlDomParser $domParser)
    {
        $this->dm = $dm;
        $this->logger = $logger;
        $this->domParser = $domParser;
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

    public function updateShows($showsPage)
    {
        $showRepository = $this->dm->getRepository('AppBundle:Show');
        $dom = $this->domParser->str_get_html($showsPage);
        /** @var \simple_html_dom_node $centerContent */
        $centerContent = $dom->find('div.mid')[0];
        /** @var \simple_html_dom_node[] $showLinks */
        $showLinks = $centerContent->find('a.bb_a');

        foreach ($showLinks as $showLink) {
            $name = str_replace(["\n", "\r"], '', $showLink->text());
            $show = $showRepository->findOneBy(['title' => $name]);
            if ($show === null) {
                $show = new Show();
                $show->setTitle($name);
                $show->setCreatedAt(new \DateTime());
                $show->setUpdatedAt(new \DateTime());
                $show->setUrl($showLink->attr['href']);
                $this->dm->persist($show);

                $this->logger->info('Добавлен новый сериал: ' . $name);
            }

            $this->dm->flush();
        }
    }

    public function updateShowStatus(Show $show, $showPage)
    {
        $dom = $this->domParser->str_get_html($showPage);
        /** @var \simple_html_dom_node $centerContent */
        $centerContent = $dom->find('div.mid')[0];
        if (mb_strpos($centerContent->text(), 'Статус: закончен', 0, 'UTF-8') !== false) {
            $show->setIsClosed(true);
            $this->logger->info('Сериал "' . $show->getTitle() . '" закрыт');
            $this->dm->flush();
        }
    }

    private function parseEpisodes(\SimpleXMLElement $rss)
    {
        $showRepository = $this->dm->getRepository('AppBundle:Show');

        $shows = $showRepository->findAll();
        foreach ($rss->channel->item as $item) {
            $title = (string)$item->title;
            $show = $this->findShowByEpisodeTitle($shows, $title);

            if ($show === null) {
                $this->logger->error('Сериал для эпизода не найден:' . $title);
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
        $titleBeginAt = mb_strpos($title, ').', 0, 'UTF-8') + 2;
        $episodeTitle = trim(mb_substr($title, $titleBeginAt, 1024, 'UTF-8'));
        $episodeTitle = preg_replace('/\[.+?\]/', '', $episodeTitle);
        $episodeTitle = preg_replace('/\(S\d+E\d+\)/', '', $episodeTitle);
        $episodeTitle = trim($episodeTitle, ' .');

        $matches = [];
        if (preg_match('/\(S(\d+)E(\d+)\)/', $title, $matches)) {
            $seasonNumber = (int)$matches[1];
            $episodeNumber = (int)$matches[2];

            $episode = $show->getEpisodeByNumbers($seasonNumber, $episodeNumber);
            if ($episode !== null) {
                return;
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