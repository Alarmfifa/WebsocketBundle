<?php

namespace Varspool\WebsocketBundle\Command;

use Wrench\Server;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StartCommand extends WebsocketCommand
{

    /**
     * @see Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('websocket:start')
            ->setDescription('Start websocket server daemon')
            ->addArgument(
                'server_name',
                InputArgument::OPTIONAL,
                'The server name (from your varspool_websocket configuration)',
                'default'
            );
    }

    /**
     * @see Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $name = $input->getArgument('server_name');
        parent::setConfName($name);
        
        if (parent::checkPid()) {
            $output->writeln('<error>PID file already exists. Maybe the server is running?</error>');
            return;
        }
        
        // fork process for demonizing
        $pid = pcntl_fork();
        
        if ($pid < 0) {
        	$output->writeln('<error>Unable to start the server process</error>');
        	return 1;
        }
        
        if ($pid > 0) {
        	$output->writeln(sprintf('<info>%s WebSocket server is running</info>', $name));
        	// stop parent process
        	return;
        }
        
        if (posix_setsid() < 0) {
        	$output->writeln('<error>Unable to make a process as session leader</error>');
        	return 1;
        }

        $manager = $this->getContainer()->get('varspool_websocket.server_manager');
        
        // use Symfony's Monolog for logging
        $logger = $this->getContainer()->get('logger');
        $manager->setLogger(function ($message, $level) use ($logger) {
            $logger->log( $level, $message );
        });
        
        // create pid file and handle system signals
        parent::createPid();
        parent::handleSignal();
        
        // use parent::listenPid() method as callable to shutdown the server
        $server = $manager->getServer($name);
        $server->run(array('Varspool\WebsocketBundle\Command\WebsocketCommand', 'listenPid'));
    }
}
?>