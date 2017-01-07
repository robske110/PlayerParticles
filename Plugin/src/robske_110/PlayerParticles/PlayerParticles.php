<?php

namespace robske_110\PlayerParticles;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\level\Location;

use robske_110\PlayerParticles\Model;
use robske_110\PlayerParticles\Listener;
use robske_110\Utils\Utils;
use robske_110\Utils\Translator;

use pocketmine\level\particle\GenericParticle;
use pocketmine\math\Vector3;

class PlayerParticles extends PluginBase{
	private $listener; 
	private $config;
	private $model = [];

	private static $defaultModels = [
		"Wing" => "wing.yml"
	];

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, []);
		if($this->config->get("ConfigVersion") != 0){
			$this->config->set('lang', 'eng');
			$this->config->set('debug', true);
			$this->config->set('ConfigVersion', 0);
		}
		$this->config->save();
		Utils::init($this, $this->config->get('debug'), "[PlayerParticles]");
		$this->initDefaults();
		$this->listener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($this->listener, $this);
	}
    
	public function getModel(string $name){
		return $this->models[$name];
	}
	
	public function registerModel(Model $model){
		$this->models[$model->getName()] = $model;
	}
	
	public function unregisterModel(){
		
	}
	
	public function render(Location $pos, Model $model){
		$layout = $model->getModelData();
		$yaw = $pos->getYaw(); /* 0-360 DEGREES */
		$yaw -= 45;
		$yawRad = (($yaw * -1) * M_PI / 180); /* RADIANS - Don't ask me why inverted! */
		
		switch($model->getModelType()){
			case "back":
				$y = $pos->getY() + 2;
				$px = $pos->x;
				$pz = $pos->z;
				if($model->getCenterMode() == Model::CENTER_STATIC){
					$amb = substr(max($layout), 0, 1) * 0.25 / 2;
					$amb += 0.25 / 2;
				}
				foreach($layout as $layer){
					$y -= 0.25;
					$diffx = 0;
					$diffz = 0;
					if($model->getCenterMode() == Model::CENTER_DYNAMIC){
						$amb = strlen($layer) * 0.25 / 2;
						$amb += 0.25 / 2;
					}else{
						$layer = substr($layer, 1);
					}
					for($verticalpos = strlen($layer) - 1; $verticalpos >= 0; $verticalpos--){
						$cos = cos($yawRad);
						$sin = sin($yawRad); 
						$rx = $diffx * $cos + $diffz * $sin;
						$rz = -$diffx * $sin + $diffz * $cos;
						$behind = $yaw - 45;
						$bx = cos($behind * M_PI / 180) * 0.25;
						$bz = sin($behind * M_PI / 180) * 0.25;
						$right = $yaw + 225;
						$cx = cos($right * M_PI / 180) * $amb;
						$cz = sin($right * M_PI / 180) * $amb;
						$fx = $px + $rx + $bx + $cx;
						$fz = $pz + $rz + $bz + $cz;
						if($layer[$verticalpos] == "P"){
							$particleObject = new GenericParticle(new Vector3($fx, $y, $fz), 7);
						$pos->getLevel()->addParticle($particleObject);
						}
						$diffx += 0.25;
						$diffz += 0.25;
					}
				}
			break;
			default:
				Utils::critical("Failed to render Model '".$model->getName()."' ");
			break;
		}
	}
	
	private function initDefaults(){
		foreach(self::$defaultModels as $name => $fileName){
			$res = $this->saveResource($fileName);
			try{
				$cfg = new Config($this->getDataFolder().$fileName, Config::YAML);
			}catch(\Throwable $t){
				$this->getLogger()->logException($t);
			}
			if($cfg->check()){
				$this->registerModel(new Model($this, $cfg->getAll(), $name));
			}else{
				Utils::critical("Failed to load config for default Model '".$name."! Check for parse errors above.");
			}
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!