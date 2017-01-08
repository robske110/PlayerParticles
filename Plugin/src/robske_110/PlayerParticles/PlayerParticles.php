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
	private $models = [];
	private $defaultModels = [];
	
	const DEG_TO_RAD = M_PI / 180;

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
    
	public function getModel(string $registeredName): Model{
		return $this->models[$registeredName];
	}
	
	/**
	 * @param string $name The name of your searched model(s)
	 * 
	 * Note: This function should not be needed!
	 *
	 * @return array [$registeredName => $model]
	 */
	public function getModelsByName(string $name): array{
		$models = [];
		foreach($this->models as $registeredName => $model){
			if($model->getName() == $name){
				$models[$registeredName] = $model;
			}
		}
		return $models;
	}
	
	/**
	  * @param Model $model The model to be registered
	  * 
	  * Note: If a model with the same level already exists, it will be saved with a number at
	  * the end e.g. Wing1. That name will be returned, so be sure to save that name.
	  *
	  * @return string Returns the index name
	  */
	public function registerModel(Model $model): string{
		if($model->getName() == ""){
			Utils::debug("Attempted to register a Model with empty name!");
			return "";
		}
		if(isset($this->models[$model->getName()])){
			Utils::debug("Model '".$model->getName()."' already exists, incrementing name.");
			$i = 1;
			$foundFreeName = false;
			while($foundFreeName){
				$name = $model->getName().$i;
				if(!isset($name)){
					$foundFreeName = true;
				}
				$i++;
			}
			$this->models[$name] = $model;
			return $name;
		}
		$this->models[$model->getName()] = $model;
		return $model->getName();
	}
	
	public function unregisterModel(Model $model, string $registeredName = ""): bool{
		if($model->getName() == ""){
			Utils::debug("Attempted to unregister a Model with empty name!");
			return false;
		}
		if(isset($this->models[$model->getName()])){
			unset($this->models[$model->getName()]);
		}
	}
	
	public function render(Location $pos, Model $model): bool{
		$layout = $model->getModelData();
		$yaw = $pos->getYaw(); /* 0-360 DEGREES */
		$yaw -= 45;
		$sp = $model->getSpacing();
		$yawRad = ($yaw * -1 * self::DEG_TO_RAD); /* RADIANS - Don't ask me why inverted! */
		
		switch($model->getModelType()){
			case "back":;
				$y = $pos->getY() + 2;
				$px = $pos->x;
				$pz = $pos->z;
				if($model->getCenterMode() == Model::CENTER_STATIC){
					$amb = max($model->getStrlenMap()) * $sp / 2;
					$amb += $amb / M_PI; /* Please just don't ask me why! */
				}
				foreach($layout as $layer){
					$y -= $sp;
					$diffx = 0;
					$diffz = 0;
					if($model->getCenterMode() == Model::CENTER_DYNAMIC){
						$amb = strlen($layer) * $sp / 2;
						$amb += $amb / M_PI;
					}
					for($verticalpos = strlen($layer) - 1; $verticalpos >= 0; $verticalpos--){
						$cos = cos($yawRad);
						$sin = sin($yawRad); 
						$rx = $diffx * $cos + $diffz * $sin;
						$rz = -$diffx * $sin + $diffz * $cos;
						$behind = $yaw - 45;
						$bx = cos($behind * self::DEG_TO_RAD) * $sp;
						$bz = sin($behind * self::DEG_TO_RAD) * $sp;
						$right = $yaw + 225;
						$cx = cos($right * self::DEG_TO_RAD) * $amb;
						$cz = sin($right * self::DEG_TO_RAD) * $amb;
						$fx = $px + $rx + $bx + $cx;
						$fz = $pz + $rz + $bz + $cz;
						if($layer[$verticalpos] == "P"){
							$particleObject = new GenericParticle(new Vector3($fx, $y, $fz), 7);
							$pos->getLevel()->addParticle($particleObject);
						}
						$diffx += $sp;
						$diffz += $sp;
					}
				}
			break;
			default:
				Utils::critical("Failed to render Model '".$model->getName()."': Unknown modeltype '".$model->getModelType()."'.");
				return false;
			break;
		}
		return true;
	}
	
	public function getRegisteredModels(): array{
		return $this->models;
	}
	
	public function getDefaultModels(): array{
		return $this->defaultModels;
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
				if($registerName = $this->registerModel(new Model($this, $cfg->getAll(), $name) !== ""){
					$this->defaultModels[$registerName] = $name;
				}else{
					Utils::critical("Failed to register default Model '".$name."! Did you modify it incorrectly?");
				}
			}else{
				Utils::critical("Failed to load config for default Model '".$name."! Check for parse errors above.");
			}
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!