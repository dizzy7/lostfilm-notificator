<?php

namespace AppBundle\Command;

use AppBundle\Document\Show;
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
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $showRepository = $dm->getRepository('AppBundle:Show');
        $domParser = $this->getContainer()->get('simple_dom_parser');

        try {
            $showsPage = iconv('windows-1251', 'utf-8', $grabber->getPage('/serials.php'));
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $dom = $domParser->str_get_html($showsPage);
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
                $dm->persist($show);

                $output->writeln('<info>Добавлен новый сериал:</info>' . $name);
            }

            $dm->flush();
        }
    }
}
