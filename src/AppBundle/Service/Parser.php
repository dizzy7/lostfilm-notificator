<?php

namespace AppBundle\Service;

use AppBundle\Document\Episode;
use AppBundle\Document\Link;
use AppBundle\Document\LostfilmShow;
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









}
