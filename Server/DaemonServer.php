<?php
namespace Varspool\WebsocketBundle\Server;

use Wrench\Server;

class DaemonServer extends Server
{
    /**
     * Fork original Server class with possibility to shutdown it
     * 
     * {@inheritDoc}
     * @see \Wrench\Server::run()
     */
    public function run( callable $finish = null )
    {
        $this->connectionManager->listen();
        
        if (!is_callable($finish) ) {
            $finish = function() { return true; };
        }
        
        while ( $finish() ) {
            $this->connectionManager->selectAndProcess();
        
            foreach($this->applications as $application) {
                if(method_exists($application, 'onUpdate')) {
                    $application->onUpdate();
                }
            }        
        }        
    }
    
    
    
}
?>