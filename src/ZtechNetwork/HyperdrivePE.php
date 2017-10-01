<?php
namespace ZtechNetwork;

use pocketmine\plugin\PluginBase;
use pocketmine\pligin\Plugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\EntityDamageEvent;
use pocketmine\event\player\BlockBreakEvent;
use pocketmine\event\player\BlockPlaceEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\block\Block;
use pocketmine\tile\Sign;
use pocketmine\item\Item;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as C;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\level\Position;

class HyperdrivePE extends PluginBase implements Listener {
	
	const VERSION = "1.0.0";
	
	public $prefix = C::GRAY . "[" . C::WHITE . C::BOLD . "S" . C::RED . "G" . C::RESET . C::GRAY . "] ";
	public $mode = 0;
	public $courses = array();
	public $currentLevel = "";
	
	public function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents($this ,$this);
		$this->getLogger()->info(C::GREEN . "HyperdrivePE has successfully loaded!");
		$this->saveResource("config.yml");
		@mkdir($this->getDataFolder());
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		if($config->get("courses")!=null)
		{
			$this->courses = $config->get("courses");
		}
		foreach($this->courses as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
		if($config->get("lightning_effect")==null){
			$config->set("lightning_effect","ON");
		}
		$config->save();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 10);
	}
	
	public function giveToolBarItem(PlayerItemHeldEvent $e){
		$p = $e->getPlayer();
		$menu = rand(1);
		switch($menu){
			case 1:
				$p->getInventory()->addItem(Item::get(378,0,1));
				$p->getInventory()->addItem(Item::get(46,0,1));
				
				$p->sendMessage(C::AQUA."Use the TNT to rage quit.");
				$p->sendMessage(C::AQUA."Use the Magma Cream to go back to last checkpoint.");
				break;	
		}
	}
	
	public function playerJoin($spawn){
		$player->teleport(new Vector3($x, $y, $z, $level));
		$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
		$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(),
				$spawn->getFloorZ()); $player->teleport($spawn,0,0);
	}
	
	public function onMove(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->courses))
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$sofar = $config->get($level . "StartTime");
			if($sofar > 0)
			{
				$to = clone $event->getFrom();
				$to->yaw = $event->getTo()->yaw;
				$to->pitch = $event->getTo()->pitch;
				$event->setTo($to);
			}
		}
	}
	
	public function onDamage(EntityDamageEvent $event)
	{
		if ($event->getEntity() instanceof Player) {
			$level = $event->getEntity()->getLevel()->getFolderName();
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			if ($config->get($level . "Time") != null) {
				if ($config->get($level . "Time") > null && $config->get($level . "Time") <= null) {
					$event->setCancelled(true);
				}
			}
		}
	}
	
	public function onBlockBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->courses))
		{
			$event->setCancelled(true);
		}
	}
	
	public function onBlockPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->courses))
		{
			$event->setCancelled(true);
		}
	}
	
	public function onCommand(CommandSender $player, Command $cmd, string $label, array $args) : bool{
		switch($cmd->getName()){
			case "hyper":
				if($player->isOp())
				{
					if(!empty($args[0]))
					
					{
						if($args[0]=="create")
						{
							if(!empty($args[1]))
							{
								if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
								{
									$this->getServer()->loadLevel($args[1]);
									$this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
									array_push($this->courses,$args[1]);
									$this->currentLevel = $args[1];
									$this->mode = 1;
									$player->sendMessage($this->prefix . "You are about to register a hyperdrive map, tap to set checkpoints.");
									$player->setGamemode(1);
									$player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(),0,0);
								}
								else
								{
									$player->sendMessage($this->prefix . "There is no world with this name.");
								}
							}
							else
							{
								$player->sendMessage($this->prefix . "HyperdrivePE Commands!");
								$player->sendMessage($this->prefix . "/hyper create [world] Creates a hyperdrive game in the specified world!");
								$player->sendMessage($this->prefix . "/hyper leave Allows player to leave the current game.");
							}
						}
						else
						{
							$player->sendMessage($this->prefix . "There is no such command.");
						}
					}
					else
					{
						$player->sendMessage($this->prefix . "HyperdrivePE Commands!");
						$player->sendMessage($this->prefix . "/hyper create [world] Creates a hyperdrive game in the specified world!");
						$player->sendMessage($this->prefix . "/hyper leave Allows player to leave the current game.");
					}
				}
				return true;
		}
	}
}