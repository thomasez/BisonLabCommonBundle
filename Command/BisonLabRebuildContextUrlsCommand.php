<?php

namespace BisonLab\CommonBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;

/**
 *
 * @author    Thomas Lundquist <thomasez@bisonlab.no>
 * @copyright 2014 Repill-Linpro, 2015 - 2020 BisonLab
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 */

class BisonLabRebuildContextUrlsCommand extends Command
{
    protected static $defaultName = 'bisonlab:rebuild-context-urls';

    private $entityManager;
    private $params;

    protected function configure()
    {
        $this
            ->setDescription('Context rebuild.')
            ->addOption('context_object', null, InputOption::VALUE_REQUIRED, 'The object you want the contexts to be rebuild for. ')
            ->addOption('system', null, InputOption::VALUE_REQUIRED, 'System name, if you want to just change one system context.')
            ->setHelp(<<<EOT
This command rebuilds context URLs based on the config set in contexts.yml.
EOT
            );
    }

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $verbose        = $input->getOption('verbose') ? true : false;
        $context_object = $input->getOption('context_object');
        $system         = $input->getOption('system');
        $entityManager  = $this->entityManager;
        $params         = $this->params;

        $output->writeln(sprintf('Debug mode is <comment>%s</comment>.', $input->getOption('no-debug') ? 'off' : 'on'));
        $output->writeln('');

        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $repo = $entityManager->getRepository($context_object);

        $q = $repo
            ->createQueryBuilder('c')
            ->where('c.external_id is not null');

        if ($system) {
            $q->andWhere('c.system = :system')
              ->setParameter('system', $system);
        }
        
        $iterableResult = $q->getQuery()->iterate();

        $context_conf = $params->get('app.contexts');
        list($bundle, $contextobject) = explode(":", $context_object);
        $object = preg_replace("/Context/", "", $contextobject);
        $object_context_config = $context_conf[$bundle][$object];

        if (!$object_context_config) { 
            error_log("No config found for " . $context_object);
            exit(1);
        }
        $context_config = array();
        foreach ($object_context_config as $system => $sc) {
            foreach ($sc as $c) {
                $context_config[$system][$c['object_name']] = $c;
            }
        }

        if ($verbose) print_r($context_config);

        $rows = 0;
        while (($res = $iterableResult->next()) !== false) {

            $context = $res[0];
            if ($verbose) echo "Had: " . $context->getUrl() . "\n";
            // Gotta find the config data.

            $cconf = $context_config[$context->getSystem()][$context->getObjectName()];
            $context->setUrl(CommonController::createContextUrl(array(
                'external_id' => $context->getExternalId(),
                'object_name' => $context->getObjectName(),
                'system' => $context->getSystem(),
                ), $cconf));

            if ($verbose) echo "Got: " . $context->getUrl() . "\n";
            $entityManager->persist($context);

            $rows++;
            if ($rows > 100) {
                $entityManager->flush();
                $entityManager->clear();
                $gc = gc_collect_cycles();
                $rows = 0;
            }

        }
        $entityManager->flush();
    }
}
