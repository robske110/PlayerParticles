<?php

namespace robske_110\PlayerParticles;

use pocketmine\scheduler\PluginTask;

use robske_110\PlayerParticles\Model;
use robske_110\PlayerParticles\Listener;
use robske_110\Utils\Utils;
use robske_110\Utils\Translator;
use pocketmine\level\Location;

use pocketmine\level\particle\GenericParticle;
use pocketmine\math\Vector3;

class RenderManager extends PluginTask{
	private $main;
	private $server;

	public function __construct(PlayerParticles $main){
		parent::__construct($main);
		$this->main = $main;
		$this->server = $main->getServer();
	}
    
	public function onRun($currentTick){
		$pos = new Location(210, 70, 215, 0, 0, $this->server->getLevelByName("world"));
		$model = $this->main->getModel('Helix');
		$this->main->render($pos, $model);
		/*
		foreach($this->server->getOnlinePlayers() as $player){
			$model = $this->main->getModel('Helix');
			$additionalData = [abs($currentTick % 360 - 360)];
			echo("CurrRot:".(abs($currentTick % 360 - 360))."\n");
			$this->main->render($player, $model, $additionalData);
		}
		*/
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!