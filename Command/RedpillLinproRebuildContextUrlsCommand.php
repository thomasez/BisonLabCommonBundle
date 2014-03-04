<?php

namespace RedpillLinpro\CommonBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use RedpillLinpro\CommonBundle\Controller\CommonController as CommonController;
/**
 *
 * @author    Thomas Lundquist <thomasez@redpill-linpro.com>
 * @copyright 2014 Repill-Linpro
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 */

class RedpillLinproRebuildContextUrlsCommand extends ContainerAwareCommand
{

    private $verbose = true;

    protected function configure()
    {

        $this->setDefinition(array(
                new InputOption('context_object', '', InputOption::VALUE_REQUIRED, 'The object you want the contexts to be rebuild for. '),
                new InputOption('system', '', InputOption::VALUE_REQUIRED, 'System name, if you want to just change one system context.'),
                ))
                ->setDescription('Context rebuild.')
                ->setHelp(<<<EOT
This command rebuilds context URLs based on the config set in contexts.yml.
EOT
            );

        $this->setName('rplp:rebuild-context-urls');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->verbose        = $input->getOption('verbose') ? true : false;
        $this->context_object = $input->getOption('context_object');
        $this->system         = $input->getOption('system');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('Debug mode is <comment>%s</comment>.', $input->getOption('no-debug') ? 'off' : 'on'));
        $output->writeln('');

        gc_enable();

        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        $stmt = $this->entityManager->getConnection()->prepare(' select name, number, item_id, count(*) from port group by item_id, number, name having count(*) > 1 order by item_id;');

        $stmt->execute();

        $this->repo    = $this->entityManager
                ->getRepository($this->context_object);

        $q = $this->repo
            ->createQueryBuilder('c')
            ->where('c.external_id is not null');

        if ($this->system) {
            $q->andWhere('c.system = :system')
              ->setParameter('system', $this->system);
        }
        
        $iterableResult = $q->getQuery()->iterate();

        $context_conf = $this->getContainer()->getParameter('app.contexts');
        list($bundle, $contextobject) = explode(":", $this->context_object);
        $object = preg_replace("/Context/", "", $contextobject);
        $object_context_config = $context_conf[$bundle][$object];

        if (!$object_context_config) { 
            error_log("No config found for " . $this->context_object);
            exit(1);
        }
        $context_config = array();
        foreach ($object_context_config as $system => $sc) {
            foreach ($sc as $c) {
                $context_config[$system][$c['object_name']] = $c;
            }
        }

        if ($this->verbose) print_r($context_config);

        $rows = 0;
        while (($res = $iterableResult->next()) !== false) {

            $context = $res[0];
            if ($this->verbose) echo "Had: " . $context->getUrl() . "\n";
            // Gotta find the config data.

            $cconf = $context_config[$context->getSystem()][$context->getObjectName()];
            $context->setUrl(CommonController::createContextUrl(array(
                'external_id' => $context->getExternalId(),
                'object_name' => $context->getObjectName(),
                'system' => $context->getSystem(),
                ), $cconf));

            if ($this->verbose) echo "Got: " . $context->getUrl() . "\n";
            $this->entityManager->persist($context);

            $rows++;
            if ($rows > 100) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $gc = gc_collect_cycles();
                $rows = 0;
            }

        }
        $this->entityManager->flush();
    }
}
