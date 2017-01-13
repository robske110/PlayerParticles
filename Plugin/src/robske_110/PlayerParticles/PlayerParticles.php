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
	private $registeredDefaults = [];
	
	const DEG_TO_RAD = M_PI / 180;

	private static $defaultModels = [
		"Wing" => "wing.yml",
		"Helix" => "helix.yml"
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
		$this->lookForUserFiles();
		$this->listener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($this->listener, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RenderManager($this), 5);
		$this->rotForHeart = 0;
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
			Utils::debug("Attempted to register a Model with empty name! Did the model fail to load?");
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
	
	public function render(Location $pos, Model $model, array $additionalData = []): bool{
		try{
			$layout = $model->getModelData();
			$yaw = $pos->getYaw(); /* 0-360 DEGREES */
			$yaw -= 45;
			$sp = $model->getSpacing();
			$yawRad = ($yaw * -1 * self::DEG_TO_RAD); /* RADIANS - Don't ask me why inverted! */
		
			$px = $pos->x;
			$pz = $pos->z;
			switch($model->getModelType()){
				case "stdhelix":
					$rot = $additionalData[0];
					$rotRad = $rot * self::DEG_TO_RAD;
					$cos = cos($rotRad);
					$sin = sin($rotRad); 
					for($yaw = 0, $y = $pos->y, $t = 0.5; $y < $pos->y + 2; $yaw += (M_PI * 2) / 20, $y += 1 / 20, $t += 1 / 50){
						$diffx = -sin($yaw) * $t;
						$diffz = cos($yaw) * $t;
						$rx = $diffx * $cos + $diffz * $sin;
						$rz = -$diffx * $sin + $diffz * $cos;
						$fx = $px + $rx;
						$fz = $pz + $rz;
						$particleObject = new GenericParticle(new Vector3($fx, $y, $fz), 7);
						$pos->getLevel()->addParticle($particleObject);
					}
					$rot = $this->rotForHeart += 30;
					if($rot > 360){
						$this->rotForHeart = 0;
						$rot = 360;
					}
					$rotRad = $rot * self::DEG_TO_RAD;
					$cos = cos($rotRad);
					$sin = sin($rotRad); 
					$t = 0.6;
					$y = $pos->y + 2;
					$yaw = M_PI * 2;
					$diffx = -sin($yaw) * $t;
					$diffz = cos($yaw) * $t;
					$rx = $diffx * $cos + $diffz * $sin;
					$rz = -$diffx * $sin + $diffz * $cos;
					$fx = $px + $rx;
					$fz = $pz + $rz;
					$particleObject = new GenericParticle(new Vector3($fx, $y, $fz), 17);
					$pos->getLevel()->addParticle($particleObject);
				case "hurricane":
					$d = 10;
					$t = 5;
					$s = 0.5;
					$v = 0.25;
					$y = $pos->getY();
					//LOW_END_RADIUS_CALC
					#def t given::starttopt
					#def s given::stopendt
					#def v given::vertres
					#def d given::ydiff
					$dcpi = abs($t - $s) / ($d / $v);
					#res dcpi ret::decreasebyiter
					//END_L_E_R_C
					$p = 0.25;
					for($iy = $y + $d; $y <= $iy; $iy -= $v){
						$t -= $dcpi;
						//PARTICLES_PER_CIRCLE_CALC
						#def p give::particlesdiff
						$ppc = round((2 * M_PI * $t) / $p);
						#res ppc ret::particlespercircle
						//END_P_P_C_C
						for($yaw = 0; $yaw <= M_PI * 2; $yaw += (M_PI * 2) / $ppc){
							$diffx = -sin($yaw) * $t;
							$diffz = cos($yaw) * $t;
							$fx = $px + $diffx;
							$fz = $pz + $diffz;
							$particleObject = new GenericParticle(new Vector3($fx, $iy, $fz), 7);
							$pos->getLevel()->addParticle($particleObject);
						}
					}
				break;
				case "back":
					$y = $pos->getY() + 2;
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
							$amb += $amb / M_PI; /* Please just don't ask me why! */
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
		}catch(\Throwable $t){
			Utils::critical("Failed to render Model '".$model->getName()."':");
			$this->getLogger()->logException($t);
			return false;
		}
	}
	
	public function getRegisteredModels(): array{
		return $this->models;
	}
	
	public function getDefaultModels(): array{
		return $this->registeredDefaults;
	}
	
	public function lookForUserFiles(){
		
	}
	
	private function initDefaults(){
		foreach(self::$defaultModels as $name => $fileName){
			$res = $this->getResource($fileName);
			if($res === null){
				Utils::critical("FATAL! No resource for '".$name."' found, was expecting '".$fileName."' in resources!");
				continue;
			}elseif(is_resource($res)){
				fclose($res);
			}
			$this->saveResource($fileName);

			try{
				$cfg = new Config($this->getDataFolder().$fileName, Config::YAML);
			}catch(\Throwable $t){
				$this->getLogger()->logException($t);
			}
			if($cfg->check()){
				if($registerName = $this->registerModel(new Model($this, $cfg->getAll(), $name)) !== ""){
					$this->registeredDefaults[$registerName] = $name;
				}else{
					Utils::critical("Failed to register default Model '".$name."'! Did you modify it incorrectly?");
				}
			}else{
				Utils::critical("Failed to load config for default Model '".$name."'! Check for parse errors above.");
			}
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!