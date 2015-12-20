<?php

namespace AppBundle\Service\Grabber;

use AppBundle\Document\AnimediaShow;
use AppBundle\Document\Episode;
use AppBundle\Document\Link;
use AppBundle\Document\LostfilmShow;
use AppBundle\Repository\OptionRepository;
use AppBundle\Service\HttpGrabber;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Component\DependencyInjection\Tests\Compiler\H;

class AnimediaGrabber implements GrabberInterface
{
    private $dm;
    private $domParser;
    private $logger;
    /** @var HttpGrabber */
    private $httpGrabber;

    public function __construct(
        DocumentManager $dm,
        HtmlDomParser $domParser,
        LoggerInterface $logger,
        Client $guzzle
    ) {
        $this->dm = $dm;
        $this->domParser = $domParser;
        $this->logger = $logger;
        $this->httpGrabber = new HttpGrabber($guzzle);
    }

    public function updateShows()
    {
        $this->updateShowsList();
        $this->updateShowsStatus();
    }

    public function updateEpisodes()
    {
        try {
            $page = $this->httpGrabber->getPage('/new');
        } catch (\Exception $e) {
            $this->logger->error('Ошибка загрузки страницы с сериалами: ' . $e->getMessage());

            return;
        }

        $this->parseNewEpisodes($page);
    }

    private function updateShowsList()
    {
        try {
            $page = $this->httpGrabber->getPage('/site/list?limit=1000');
        } catch (\Exception $e) {
            $this->logger->error('Ошибка загрузки страницы с сериалами: ' . $e->getMessage());

            return;
        }

        $this->parseListPage($page);
    }

    private function parseListPage($page)
    {
        $showRepository = $this->dm->getRepository('AppBundle:AnimediaShow');
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($page);
        $finder = new \DOMXPath($dom);
        $classname = 'ads-list__item__title';
        $nodes = $finder->query("//a[contains(@class, '$classname')]");
        /** @var \DOMNode $node */
        foreach ($nodes as $node) {
            $showName = $node->nodeValue;
            $show = $showRepository->findOneBy(['title' => $showName]);
            if ($show === null) {
                $show = new AnimediaShow();
                $show->setTitle($showName);
                $show->setUrl($node->getAttribute('href'));
                $this->dm->persist($show);

                $this->logger->info('Добавлено новый сериал animedia: ' . $showName);
            }
        }

        $this->dm->flush();
    }

    private function updateShowsStatus()
    {
        /** @var AnimediaShow[] $shows */
        $shows = $this->dm->getRepository('AppBundle:AnimediaShow')->findActiveShows();

        foreach ($shows as $show) {
            try {
                $showPage = $this->httpGrabber->getPage($show->getUrl());
                $this->updateShowStatus($show, $showPage);

                sleep(mt_rand(1, 5));
            } catch (\Exception $e) {
                $this->logger->error('Ошибка загрузки страницы сериала ' . $show->getTitle() . ':' . $e->getMessage());
                sleep(60);
            }
        }
    }

    private function updateShowStatus(AnimediaShow $show, $showPage)
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($showPage);
        $finder = new \DOMXPath($dom);
        $classname = 'director';
        $nodes = $finder->query("//*[contains(@class, '$classname')]");
        if ($nodes->length > 0) {
            $statusNode = $nodes->item(0);
            $text = $statusNode->textContent;
            if (mb_strpos($text, 'Завершен', 0, 'UTF-8') !== false) {
                $show->setClosed(true);
                $this->dm->flush();
            }
        }
    }

    private function parseNewEpisodes($page)
    {
        $animediaShowRepository = $this->dm->getRepository('AppBundle:AnimediaShow');
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($page);
        $finder = new \DOMXPath($dom);
        $classname = 'widget__new-series__item__title';
        $nodes = $finder->query("//a[contains(@class, '$classname')]");
        foreach ($nodes as $node) {
            $showTitle = $node->getAttribute('title');
            $show = $animediaShowRepository->findOneBy(['title' => $showTitle]);
            if ($show) {
                $url = 'http://online.animedia.tv' . $node->getAttribute('href');
                $matches = [];
                if (preg_match('#Серия\s(\d+)\sиз#u', $node->parentNode->nodeValue, $matches)) {
                    $episode = $show->getEpisodeByNumbers(null, (int)$matches[1]);
                    if (!$episode) {
                        $episode = new Episode();
                        $episode->setEpisodeNumber((int)$matches[1]);

                        $link = new Link();
                        $link->setUrl($url);
                        $link->setResolution('online');
                        $episode->addLink($link);

                        $show->addEpisode($episode);

                        $this->logger->info('Новая серия сериала на animedia:' . $showTitle);
                    }

                }
            } else {
                $this->logger->error('Сериал на animedia не найден:' . $showTitle);
            }
        }
        $this->dm->flush();
    }
}