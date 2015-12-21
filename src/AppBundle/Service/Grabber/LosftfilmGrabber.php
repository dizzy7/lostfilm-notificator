<?php

namespace AppBundle\Service\Grabber;

use AppBundle\Document\Episode;
use AppBundle\Document\Link;
use AppBundle\Document\LostfilmShow;
use AppBundle\Event\NewShowEvent;
use AppBundle\Service\HttpGrabber;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LosftfilmGrabber implements GrabberInterface
{
    private $dm;
    private $domParser;
    private $logger;
    private $eventDispatcher;
    /** @var HttpGrabber */
    private $httpGrabber;

    public function __construct(
        DocumentManager $dm,
        HtmlDomParser $domParser,
        LoggerInterface $logger,
        Client $guzzle,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->dm = $dm;
        $this->domParser = $domParser;
        $this->logger = $logger;
        $this->httpGrabber = new HttpGrabber($guzzle);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function updateShows()
    {
        $this->updateShowsList();
        $this->updateShowsStatus();
    }

    public function updateEpisodes()
    {
        try {
            $rssFeed = $this->httpGrabber->getPage('/rssdd.xml');
        } catch (\Exception $e) {
            $this->logger->error('Ошибка загрузки rss:'.$e->getMessage());

            return 1;
        }
        $rss = simplexml_load_string($rssFeed);

        $this->parseRss($rss);
    }

    private function updateShowsList()
    {
        try {
            $showsPage = iconv('windows-1251', 'utf-8', $this->httpGrabber->getPage('/serials.php'));
        } catch (\Exception $e) {
            $this->logger->error('Ошибка загрузки страницы с сериалами: '.$e->getMessage());

            return;
        }

        $showRepository = $this->dm->getRepository('AppBundle:LostfilmShow');
        $dom = $this->domParser->str_get_html($showsPage);
        if ($dom) {
            /** @var \simple_html_dom_node $centerContent */
            $centerContent = $dom->find('div.mid')[0];
            /** @var \simple_html_dom_node[] $showLinks */
            $showLinks = $centerContent->find('a.bb_a');

            foreach ($showLinks as $showLink) {
                $name = str_replace(["\n", "\r"], '', $showLink->text());
                $show = $showRepository->findOneBy(['title' => $name]);
                if ($show === null) {
                    $show = new LostfilmShow();
                    $show->setTitle($name);
                    $show->setCreatedAt(new \DateTime());
                    $show->setUpdatedAt(new \DateTime());
                    $show->setUrl('http://www.lostfilm.tv'.$showLink->attr['href']);
                    $this->dm->persist($show);

                    $this->eventDispatcher->dispatch(NewShowEvent::EVENT_NAME, new NewShowEvent($show));

                    $this->logger->info('Добавлен новый сериал: '.$name);
                }
            }

            $this->dm->flush();
        } else {
            $this->logger->error('Ошибка парсинга страницы с сериалами: dom не обработан', ['page' => $showsPage]);
        }
    }

    private function updateShowsStatus()
    {
        /** @var LostfilmShow[] $shows */
        $shows = $this->dm->getRepository('AppBundle:LostfilmShow')->findActiveShows();

        foreach ($shows as $show) {
            try {
                $showPage = iconv('windows-1251', 'utf-8', $this->httpGrabber->getPage($show->getUrl()));
                $this->updateShowStatus($show, $showPage);

                sleep(mt_rand(1, 5));
            } catch (\Exception $e) {
                $this->logger->error('Ошибка загрузки страницы сериала '.$show->getTitle().':'.$e->getMessage());
                sleep(60);
            }
        }
    }

    private function updateShowStatus(LostfilmShow $show, $showPage)
    {
        $dom = $this->domParser->str_get_html($showPage);
        /** @var \simple_html_dom_node $centerContent */
        $centerContent = $dom->find('div.mid')[0];
        if (mb_strpos($centerContent->text(), 'Статус: закончен', 0, 'UTF-8') !== false) {
            $show->setClosed(true);
            $this->logger->info('Сериал "'.$show->getTitle().'" закрыт');
            $this->dm->flush();
        }
    }

    private function parseRss(\SimpleXMLElement $rss)
    {
        $showRepository = $this->dm->getRepository('AppBundle:LostfilmShow');

        $shows = $showRepository->findAll();
        foreach ($rss->channel->item as $item) {
            $title = (string) $item->title;
            $link = (string) $item->link;
            $show = $this->findShowByEpisodeTitle($shows, $title);

            if ($show === null) {
                $this->logger->error('Сериал для эпизода не найден:'.$title);
                continue;
            }

            $this->addEpisodeIfNotExists($show, $title, $link);
        }

        $this->dm->flush();
    }

    private function addEpisodeIfNotExists(LostfilmShow $show, $title, $link)
    {
        $titleBeginAt = mb_strpos($title, ').', 0, 'UTF-8') + 2;
        $episodeTitle = trim(mb_substr($title, $titleBeginAt, 1024, 'UTF-8'));
        $episodeTitle = preg_replace('/\[.+?\]/', '', $episodeTitle);
        $episodeTitle = preg_replace('/\(S\d+E\d+\)/', '', $episodeTitle);
        $episodeTitle = trim($episodeTitle, ' .');

        $matches = [];
        if (preg_match('/\(S(\d+)E(\d+)\)/', $title, $matches)) {
            $seasonNumber = (int) $matches[1];
            $episodeNumber = (int) $matches[2];

            $episode = $show->getEpisodeByNumbers($seasonNumber, $episodeNumber);
            if ($episode !== null) {
                $this->addLinkToEpisode($episode, $link);

                return;
            }

            $episode = new Episode();
            $episode->setCreatedAt(new \DateTime());
            $episode->setTitle($episodeTitle);
            $episode->setSeasonNumber($seasonNumber);
            $episode->setEpisodeNumber($episodeNumber);
            $this->addLinkToEpisode($episode, $link);

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

    private function addLinkToEpisode(Episode $episode, $url)
    {
        $existLinks = $episode->getLinks()->map(function (Link $link) {
            return $link->getUrl();
        })->toArray();

        if (in_array($url, $existLinks, true)) {
            return;
        }

        $resolution = 'SD';
        if (strpos($url, '.720p.')) {
            $resolution = '720p';
        } elseif (strpos($url, '.1080p') !== false) {
            $resolution = '1080p';
        }

        $link = new Link();
        $link->setUrl($url);
        $link->setResolution($resolution);

        $episode->addLink($link);
    }

    /**
     * @param $shows LostfilmShow[]
     * @param $title
     *
     * @return LostfilmShow|null
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

        return;
    }
}
