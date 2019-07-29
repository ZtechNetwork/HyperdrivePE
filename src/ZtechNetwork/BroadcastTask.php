<?php

declare(strict_types=1);

namespace HyperdrivePE\BroadcastTask;

use pocketmine\scheduler\Task;
use pocketmine\Server;

class BroadcastTask extends Task{

	/** @var Server */
	private $server;

	public function __construct(Server $server){
		$this->server = $server;
	}

	public function onRun(int $currentTick) : void{
		$this->server->broadcastMessage("[Hyperdrive] I've run on tick " . $currentTick);
	}
}
