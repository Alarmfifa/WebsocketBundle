<?php

namespace Varspool\WebsocketBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StopCommand extends WebsocketCommand
{
    /**
     * @see Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('websocket:stop')
            ->setDescription('Stop websocket server')
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
        
        if (!parent::checkPid()) {
            $output->writeln("<error>PID file doesn't exist. Maybe the server isn't running?</error>");
            return;
        }
        
        $pid = parent::getPid();
   
        // send sigterm signal to shutdown server
        posix_kill($pid, SIGTERM);
        parent::deletePid();

        $output->writeln(sprintf('<info>Stopped %s WebSocket server</info>', $name));
    
    }
}

?>