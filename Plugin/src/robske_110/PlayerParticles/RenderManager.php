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
		foreach($this->main->renderJobs as $renderJob){
			if($renderJob->isActive()){
				$location = $renderJob->getLocation();
				$models = $renderJob->getModels();
				foreach($models as $model){
					if(!$model->isActive()){
						continue;
					}
					if($model->needsTickRot()){
						$model->setRuntimeData(Model::DATA_TICK_ROT, abs($currentTick % 360 - 360)); #Inverted 360 degree rotation
					}
					$this->main->render($location, $model);
				}
			}
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!