<?php
namespace ZtechNetwork;

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
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
	
	const NAME = "Hyperdrive PE";
	const CODENAME = "[BETA]";
	const VERSION = "1.0.0";
	
	public $prefix = C::GRAY . "[" . C::WHITE . C::BOLD . "S" . C::RED . "G" . C::RESET . C::GRAY . "] ";
	public $mode = 0;
	public $courses = array();
	public $currentLevel = "";
	
	public function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this ,$this);
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
		if($config->get("firework_effect")==null){
			$config->set("firwork_effect","ON");
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
	
	public function onCommand(CommandSender $player, Command $cmd, string $label, array $args) : bool {
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
								$player->sendMessage($this->prefix . "/hyper <create> [world] Creates a hyperdrive game in the specified world!");
								$player->sendMessage($this->prefix . "/hyper <quit> Allows player to quit the current game.");
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
						$player->sendMessage($this->prefix . "/hyper <create> [world] Creates a hyperdrive game in the specified world!");
						$player->sendMessage($this->prefix . "/hyper <quit> Allows player to quit the current game.");
					}
				}
				return true;
		}
	}
				
	public function onInteract(PlayerInteractEvent $event)
				{
					$player = $event->getPlayer();
					$block = $event->getBlock();
					$tile = $player->getLevel()->getTile($block);
					
					if($tile instanceof Sign)
					{
						if($this->mode==26)
						{
							$tile->setText(C::GRAY . "[§2Join§7]",C::BLUE  . "0 / 20",$this->currentLevel,$this->prefix);
							$this->refreshCourses();
							$this->currentLevel = "";
							$this->mode = 0;
							$player->sendMessage($this->prefix . "The course has been registered successfully!");
						}
						else
						{
							$text = $tile->getText();
							if($text[3] == $this->prefix)
							{
								if($text[0]==C::WHITE . "[§bJoin§f]")
								{
									$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
									$level = $this->getServer()->getLevelByName($text[2]);
									$aop = count($level->getPlayers());
									$thespawn = $config->get($text[2] . "Spawn" . ($aop+1));
									$spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$level);
									$level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
									$player->teleport($spawn,0,0);
									$player->getInventory()->clearAll();
									$player->sendMessage("§7§l[§fHyper§cdrive§7] You have just joined",$this->currentLevel);
									$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
									$player->getInventory()->setChestplate(Item::get(Item::ELYTRA));
									$player->getInventory()->setItem(0, Item::get(Item::MAGMA_CREAM, 0, 1));
									$player->getInventory()->setItem(0, Item::get(Item::TNT, 0, 2));
									$player->getInventory()->sendArmorContents($player);
									$player->getInventory()->setHotbarSlotIndex(0, 0);
								}
								else
								{
									$player->sendMessage($this->prefix . "This game is full, please try again later.");
								}
							}
						}
					}
					else if($this->mode>=1&&$this->mode<=24)
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
						$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
						$player->sendMessage($this->prefix . "Spawn " . $this->mode . " has been registered!");
						$this->mode++;
						if($this->mode==25)
						{
							$player->sendMessage($this->prefix . "Now tap on a deathmatch spawn.");
						}
						$config->save();
					}
					else if($this->mode==25)
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
						$level = $this->getServer()->getLevelByName($this->currentLevel);
						$level->setSpawn = (new Vector3($block->getX(),$block->getY()+1,$block->getZ()));
						$config->set("courses",$this->courses);
						$player->sendMessage($this->prefix . "Please tap the sign to register this hyperdrive course!");
						$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
						$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
						$player->teleport($spawn,0,0);
						$config->save();
						$this->mode=26;
					}
				}
				
	public function refreshCourses()
				{
					$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
					$config->set("courses",$this->courses);
					foreach($this->courses as $course)
					{
						$config->set($course . "PlayTime", 780);
						$config->set($course . "StartTime", 60);
					}
					$config->save();
				}
				
	public function onDisable()
				{
					$this->saveResource("config.yml");
				}
		}
		class RefreshSigns extends Task {
	public $prefix = C::GRAY . "[" . C::WHITE . C::BOLD . "S" . C::RED . "G" . C::RESET . C::GRAY . "] ";
	
	public function __construct($plugin)
			{
				$this->plugin = $plugin;
				parent::__construct($plugin);
			}
			
	public function onRun($tick)
			{
				$allplayers = $this->plugin->getServer()->getOnlinePlayers();
				$level = $this->plugin->getServer()->getDefaultLevel();
				$tiles = $level->getTiles();
				foreach($tiles as $t) {
					if($t instanceof Sign) {
						$text = $t->getText();
						if($text[3]==$this->prefix)
						{
							$aop = 0;
							foreach($allplayers as $player){if($player->getLevel()->getFolderName()==$text[2]){$aop=$aop+1;}}
							$ingame = C::WHITE . "[§bJoin§f]";
							$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
							if($config->get($text[2] . "PlayTime")!=780)
							{
								$ingame = C::GRAY . "[§cRunning§7]";
							}
							else if($aop>=24)
							{
								$ingame = C::GRAY . "[§4Full§7]";
							}
							$t->setText($ingame,C::BLUE  . $aop . " / 10",$text[2],$this->prefix);
						}
					}
				}
			}
		}
		class GameSender extends Task {
	public $prefix = C::GRAY . "[" . C::WHITE . C::BOLD . "S" . C::RED . "G" . C::RESET . C::GRAY . "] ";
	public function __construct($plugin)
			{
				$this->plugin = $plugin;
				parent::__construct($plugin);
			}
			
	public function onRun($tick)
			{
				$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
				$courses = $config->get("courses");
				if(!empty($courses))
				{
					foreach($courses as $course)
					{
						$time = $config->get($course . "PlayTime");
						$timeToStart = $config->get($course . "StartTime");
						$levelCourse = $this->plugin->getServer()->getLevelByName($course);
						if($levelCourse instanceof Level)
						{
							$playerscourse = $levelCourse->getPlayers();
							if(count($playersCourse)==0)
							{
								$config->set($course . "PlayTime", 780);
								$config->set($course . "StartTime", 60);
							}
							else
							{
								if(count($playersCourse)>=2)
								{
									if($timeToStart>0)
									{
										$timeToStart--;
										foreach($playersCourse as $pl)
										{
											$level=$pl->getLevel();
											$level->addSound(new FizzSound($pl));
											$pl->sendPopup(C::GRAY . "Starting in " . $timeToStart . " Seconds");
										}
										if($timeToStart<=0)
										{
											foreach($playersCourse as $pl)
											{
												$pl->sendMessage($this->prefix . C::GREEN . "Good Luck!");}
										}
										$config->set($course . "StartTime", $timeToStart);
									}
									else
									{
										$aop = count($levelCourse->getPlayers());
										if($aop==1)
										{
											foreach($playersCourse as $pl)
											{
												$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
												$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
												$pl->teleport($spawn,0,0);
											}
											$config->set($course . "PlayTime", 780);
											$config->set($course . "StartTime", 60);
										}
										$time--;
										if($time>=180)
										{
											$time2 = $time - 180;
											$minutes = $time2 / 60;
											foreach($playersCourse as $pl)
											{
												$pl->sendPopup($this->prefix . $time2 . " left in the match!");
											}
											if(is_int($minutes) && $minutes>0)
											{
												foreach($playersCourse as $pl)
												{
													$pl->sendMessage($this->prefix . $minutes . " minutes to deathmatch");
												}
											}
											else if($time2 == 300)
											{
												foreach($playersCourse as $pl)
												{
													$pl->sendMessage($this->prefix . "");
												}
												$this->refillChests($levelCourse);
											}
											else if($time2 == 30 || $time2 == 15 || $time2 == 10 || $time2 ==5 || $time2 ==4 || $time2 ==3 || $time2 ==2 || $time2 ==1)
											{
												foreach($playersCourse as $pl)
												{
													$pl->sendMessage($this->prefix . $time2 . "");
												}
											}
											if($time2 <= 0)
											{
												$spawn = $levelCourse->getSafeSpawn();
												$levelCourse->loadChunk($spawn->getX(), $spawn->getZ());
												foreach($playersCourse as $pl)
												{
													$pl->teleport($spawn,0,0);
												}
											}
										}
										else
										{
											$minutes = $time / 60;
											if(is_int($minutes) && $minutes>0)
											{
												foreach($playersCourse as $pl)
												{
													$pl->sendMessage($this->prefix . $minutes . " minutes remaining");
												}
											}
											else if($time == 30 || $time == 15 || $time == 10 || $time ==5 || $time ==4 || $time ==3 || $time ==2 || $time ==1)
											{
												foreach($playersCourse as $pl)
												{
													$pl->sendMessage($this->prefix . $time . " seconds remaining");
												}
											}
											if($time <= 780)
											{
											}
											
											if($time <= 0)
											{
												$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
												$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
												foreach($playersCourse as $pl)
												{
													$pl->teleport($spawn,0,0);
													$pl->sendMessage($this->prefix . "No winner this time!");
													$pl->getInventory()->clearAll();
												}
												$time = 780;
											}
										}
										$config->set($Course . "PlayTime", $time);
									}
								}
								else
								{
									if($timeToStart<=0)
									{
										foreach($playersCourse as $pl)
										{
											$pl->getInventory()->clearAll();
											$pl->sendMessage($this->prefix . C::GRAY . "You won the match!");
											$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
											$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
											$pl->teleport($spawn,0,0);
										}
										$config->set($course . "PlayTime", 780);
										$config->set($course . "StartTime", 60);
									}
									else
									{
										foreach($playersCourse as $pl)
										{
											$pl->sendPopup(C::RED . "A game requires 2 players!");
											
										}
										$config->set($course . "PlayTime", 780);
										$config->set($course . "StartTime", 60);
									}
								}
							}
						}
					}
				}
				$config->save();
	   }
			
}