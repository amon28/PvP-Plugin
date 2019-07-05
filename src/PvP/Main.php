<?php
namespace PvP;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat as C;
use pocketmine\utils\Config;
use pocketmine\inventory\Inventory;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocetmine\scheduler\TaskScheduler;
use pocketmine\scheduler\TaskHandler;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase implements Listener{
    
    public $winner;
    
    public $players = [];
    public $coords1 = [];
    public $coords2 = [];
    public $inv1 = [];
    public $inv2 = [];
    
    //Config Vars
    public $world;
    public $player1;
    public $player2;
    public $items;
    //------------
    
    public $tasks = [];
    
    public $back = 0;
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        
    if(!is_dir($this->getDataFolder())){
			@mkdir($this->getDataFolder());
		}
		if(!file_exists($this->getDataFolder() . "config.yml")){
			$this->saveDefaultConfig();
		}
	$this->world = $this->getConfig()->get("World");
    $this->player1 = $this->getConfig()->get("Player1");
    $this->player2 = $this->getConfig()->get("Player2");
    $this->items = $this->getConfig()->get("Items");
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, string $label,array $args) : bool {
    if($cmd == "pvp"){
    
    if(isset($args[0])){ 
    if($sender->isOp() or $sender->hasPermission("pvp.op")){
    $plp = $sender->getPlayer()->getPosition();
    $pos = $plp->getX().",".$plp->getY().",".$plp->getZ();
    $wrld = $sender->getPlayer()->getLevel()->getName();
    
    switch($args[0]){
        
    case "world":
    $this->getConfig()->set("World",strval($wrld));
    $this->getConfig()->save();
    $sender->sendMessage(C::YELLOW.C::UNDERLINE."World pvp set"); 
    return true;
    break;
    
    case "p1":
    $p1 = explode(",",strval($pos));
    $this->getConfig()->set("Player1",$p1);
    $this->getConfig()->save();
    $sender->sendMessage(C::YELLOW.C::UNDERLINE."Player1 spawn set");  
    return true;
    break;
    
    case "p2":
    $p2 = explode(",",strval($pos));
    $this->getConfig()->set("Player2",$p2);
    $this->getConfig()->save();
    $sender->sendMessage(C::YELLOW.C::UNDERLINE."Player2 spawn set");
    return true;
    break;
    
    case "items":
    $form = new CustomForm(function (Player $sender, $data){
        if($data === null){
            return true;
        }
   
        $cf = new Config($this->getDataFolder() . "config.yml");
      
      $name = $cf->get("Items");
      $item = explode(" ",$data[1]);
      $cf->set("Items",$item);
      $cf->save();
      
      $sender->sendMessage(C::YELLOW.C::UNDERLINE."PvP items set");
    });
    $form->setTitle("Add PvP items");
    $form->addLabel(C::YELLOW.C::UNDERLINE."Adding Items: ID,META,DAMAGE,COUNT \nex.\n1,0,64 2,0,64");
    $form->addInput("Items:","1,0,64");
    $sender->sendForm($form); 
    return true;
    break;
    
    default:
    $sender->sendMessage(C::GREEN.C::UNDERLINE."USAGE: /pvp world|items|p1|p2");
    }    
    }
    return true;
    }
    
    $pl = $this->players;
    
    //Checks if player is already in que
    if(in_array($sender->getPlayer(),$pl)){
    $sender->sendMessage(C::YELLOW.C::UNDERLINE."You are already in que");
    return true;
    }else{
    $sender->sendMessage(C::YELLOW.C::UNDERLINE."[PvP]You have joined the que!");  
    }
    
    array_push($this->players,$sender->getPlayer());
    if(count($this->players) == 3){
    array_pop($this->players);
    return true;
    }
    if(count($this->players) == 2){
    $level = $this->getServer()->getLevelByName($this->world[0]);
    $c1 = $this->player1;
    $c2 = $this->player2;
    $pos1 = new Position((int)$c1[0],(int)$c1[1],(int)$c1[2], $level);
    $pos2 = new Position((int)$c2[0],(int)$c2[1],(int)$c2[2], $level);
  
  //original player position
  $this->coords1[0] = $this->players[0]->getPosition()->getX();
  $this->coords1[1] = $this->players[0]->getPosition()->getY();
  $this->coords1[2] = $this->players[0]->getPosition()->getZ();
  $this->coords1[3] = $this->players[0]->getLevel()->getName();
  $this->inv1 = $this->players[0]->getInventory()->getContents();
  
  
 $this->coords2[0] = $this->players[1]->getPosition()->getX();
  $this->coords2[1] = $this->players[1]->getPosition()->getY();
  $this->coords2[2] = $this->players[1]->getPosition()->getZ();
  $this->coords2[3] = $this->players[1]->getLevel()->getName();
  $this->inv2 = $this->players[1]->getInventory()->getContents();
  
  
  $this->players[0]->getInventory()->clearAll();
  $this->players[1]->getInventory()->clearAll();
  
  //Put Items in Inv
  foreach($this->items as $Item){
    $i = explode(",",$Item);
    $pl1 = $this->players[0]->getInventory()->addItem(Item::get((int)$i[0],(int)$i[1],(int)$i[2]));
  
    $pl2 = $this->players[1]->getInventory()->addItem(Item::get((int)$i[0],(int)$i[1],(int)$i[2]));
  }
   $this->players[0]->teleport($pos1);
  $this->players[1]->teleport($pos2); 
  
    //Start task for pvp
    $this->lobbytask();
    }
    
    
    }else{
    $sender->sendMessage(C::RED.C::UNDERLINE."Match is already starting");  
    }
    return true;
    }
    
public function goBack(){
if($this->back == 1){
$this->back = 0;
return true;
}
$level1 = $this->getServer()->getLevelByName($this->coords1[3]);
$pos1 = new Position($this->coords1[0],$this->coords1[1],$this->coords1[2],$level1);

$this->players[0]->teleport($pos1);
$this->players[0]->getInventory()->clearAll();
foreach($this->inv1 as $i){
$this->players[0]->getInventory()->addItem($i);
}

$level2 = $this->getServer()->getLevelByName($this->coords2[3]);
$pos2 = new Position($this->coords2[0],$this->coords2[1],$this->coords2[2],$level2);
$this->players[1]->teleport($pos2);
$this->players[1]->getInventory()->clearAll();
foreach($this->inv2 as $i){
$this->players[1]->getInventory()->addItem($i);
}
unset($this->players[0]);
unset($this->players[1]);
}

public function removeTask($id) {
    // Removes the task from your array of tasks
    unset($this->tasks[$id]);
    // Cancels the task and stops it from running
    $this->getScheduler()->cancelTask($id);
$this->goBack();
}	

public function lobbytask(){
	$task = new pvpTask($this,$this->players);	
    $h = $this->getScheduler()->scheduleRepeatingTask($task,20);
	$task->setHandler($h);
    $this->tasks[$task->getTaskId()] = $task->getTaskId();				
	}
	
public function onQuit(PlayerQuitEvent $e){
$player = $e->getPlayer();
if(in_array($player,$this->players)){
if(array_search($player,$this->players) != null){
if(isset($this->tasks)){
$this->players[1]->setHealth(0);
$this->goBack();
$this->back = 1;
}else{
unset($this->players[1]);
}
}else{
if(isset($this->tasks)){
$this->players[0]->setHealth(0);
$this->goBack();
$this->back = 1;
}else{
unset($this->players[0]);   
}
}
}
}
	
    public function onDisable(){
     $this->getLogger()->info("§cOffline");
    }
}
