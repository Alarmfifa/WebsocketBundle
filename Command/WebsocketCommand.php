<?php

namespace Varspool\WebsocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Varspool\WebsocketBundle\Exception\WebSocketException;
use Varspool\WebsocketBundle\Exception\WebSocketSignalException;


/**
 *  Usefull methods for demonizing wrench websocket bundle
 */
abstract class WebsocketCommand extends ContainerAwareCommand
{
    /**
     * Server name from varspool_websocket config file
     * 
     * @var string
     */
    private $name = 'default';
    
    
    /**
     * Set server name
     *
     * @param string $title
     */
    public function setConfName($name) 
    {
        $this->name = $name;
    }
    
    /**
     * Get full path for pid file
     *
     * @return string 
     */
    private function getPidFileName()
    {
        return sys_get_temp_dir().'/websocket-'.$this->name.'.pid';
    }
    
    /**
     * Check existence of pid file
     * 
     * @return boolean
     */
    protected function checkPid() 
    {
        return file_exists($this->getPidFileName());
    }
    
    /**
     * Delete pid file
     * 
     * @throws WebSocketException
     * @return boolean
     */
    protected function deletePid()
    {
        if (!$this->checkPid()) {
            throw new WebSocketException("Pid file doesn't exist", 1);
        }
        if (!unlink($this->getPidFileName())) {
            throw new WebSocketException("Can't delete pid file.\n".error_get_last(), 4);
        }
        return true;
    }

    /**
     * Get websocket daemon process number
     *
     * @throws WebSocketException
     * @return integer
     */
    protected function getPid()
    {
        if (!$handle = fopen($this->getPidFileName(), "r")){
            throw new WebSocketException("Can't open pid file.\n".error_get_last(), 2);
        }
        if (!$pid = fread($handle, filesize($this->getPidFileName()))) {
            throw new WebSocketException("Can't read pid file.\n".error_get_last(), 3);
        }
        return $pid;
    }
    
    /**
     * Create pid file with process number inside
     * 
     * @throws WebSocketException
     * @return boolean
     */
    protected function createPid()
    {
        if (!$handle = fopen($this->getPidFileName(), "w") ){
            throw new WebSocketException("Can't open pid file.\n".error_get_last(), 2);
        }
        if (!$pid = getmypid()) {
            throw new WebSocketException("Can't get current php process id.\n".error_get_last(), 6);
        }        
        if (!fwrite($handle, $pid)) {
            throw new WebSocketException("Can't write into pid file.\n".error_get_last(), 5);
        }
        return true;
    }
    
    /**
     * Handle process's signal (sigterm) and throw exception for future catching
     * 
     * @throws WebSocketSignalException
     */
    static function handleSignal()
    {
        pcntl_signal(SIGTERM,  function($signo) { 
            throw new WebSocketSignalException('Received SIGTERM signal', 12); 
        });
    }
    
    /**
     * Check system signals and return false in time of sigterm recieving
     * 
     * @throws WebSocketException
     * @return boolean
     */
    static function listenPid()
    {
        sleep(1);
        try {
            if (!pcntl_signal_dispatch()) {
                throw new WebSocketException("Can't dispatch signals.\n".error_get_last(), 7);
            }            
        } catch (WebSocketSignalException $e) {
                return false;
        }
        return true;
    }
    
}
?>
