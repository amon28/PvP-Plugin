<?php

namespace PvP;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use pocketmine\math\Vector3;
use PvP\Main;

class pvpTask extends Task {

    public $plugin;
    public $secs=59;
    public $mins=3;
    
    public $players = [];
	
	public function __construct(Main $plugin,Array $players){
	$this->plugin = $plugin;
	$this->players = $players;
	}
	
	public function getPlugin() {
          return $this->plugin;
      }
	  	 
      public function onRun(int $tick) {
		$level = $this->getPlugin()->getConfig()->get("World");
		
		$p = $this->players;
		
		if(((int)$p[0]->getHealth()) <= 0){
		$this->stop();
		Server::getInstance()->broadcastMessage(C::YELLOW.C::UNDERLINE."[PvP]".$p[1]->getName()." has won against ".$p[0]->getName());
		}else{
		if(((int)$p[1]->getHealth()) <= 0){
		$this->stop();
		Server::getInstance()->broadcastMessage(C::YELLOW.C::UNDERLINE."[PvP]".$p[0]->getName()." has won against ".$p[1]->getName());
		}   
		}
		
		foreach($p as $pl){
		$pl->sendTip($this->mins.":".$this->secs);    
		}
		
		if($this->secs == 0){
		    if($this->mins == 0){
		        
		        Server::getInstance()->broadcastMessage(C::GREEN.C::UNDERLINE."PvP is a draw");
		    $this->stop();
		    }
		$this->secs = 60;
		$this->mins -= 1;
		}
		$this->secs -= 1;
			  //$this->getPlugin()->removeTask($this->getTaskId()); Stops the task
          }

		public function stop(){
		$this->getPlugin()->removeTask($this->getTaskId());
	  }

      }	  