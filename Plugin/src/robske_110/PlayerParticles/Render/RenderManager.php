<?php

namespace robske_110\PlayerParticles\Render;

use pocketmine\scheduler\PluginTask;

use robske_110\PlayerParticles\Render\RenderJob;
use robske_110\PlayerParticles\Render\Renderer;
use robske_110\PlayerParticles\PlayerParticles;

class RenderManager extends PluginTask{
	private $main;
	private $renderJobs = [];
	private $server;

	public function __construct(PlayerParticles $main){
		parent::__construct($main);
		$this->main = $main;
		$this->server = $main->getServer();
	}
    
	public function onRun($currentTick){
		$this->renderJobs = [];
		foreach($this->main->getServer()->getOnlinePlayers() as $player){
			$this->renderJobs[] = new RenderJob($player, $this->main->getModel("IamCone"));
		}
		foreach($this->renderJobs as $renderJob){
			if($renderJob->isActive()){
				$location = $renderJob->getLocation();
				$model = $renderJob->getModel();
				/*if(!$renderJob->isActive()){
					continue;
				}*/
				if($model->needsTickRot()){
					$model->setRuntimeData("rot", abs($currentTick % 360 - 360)); #Inverted 360 degree rotation
				}
				$this->main->getRenderer()->render($location, $model);
			}
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!