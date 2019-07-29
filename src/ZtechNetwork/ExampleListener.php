<?php

declare(strict_types=1);

namespace HyperdrivePE;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerRespawnEvent;

class ExampleListener implements Listener{

	/** @var Main */
	private $plugin;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param PlayerRespawnEvent $event
	 *
	 * @priority        NORMAL
	 * @ignoreCancelled false
	 */
	public function onSpawn(PlayerRespawnEvent $event) : void{
		$this->plugin->getServer()->broadcastMessage($event->getPlayer()->getDisplayName() . " has just spawned!");
	}
}
