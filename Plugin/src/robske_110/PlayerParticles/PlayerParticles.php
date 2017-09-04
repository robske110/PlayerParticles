<?php

namespace robske_110\PlayerParticles;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\level\Location;

use robske_110\PlayerParticles\Model\Model;
use robske_110\PlayerParticles\Model\Model2DMap;
use robske_110\PlayerParticles\EventListener;
use robske_110\PlayerParticles\Render\RenderManager;
use robske_110\Utils\Utils;
use robske_110\Utils\Translator;

use pocketmine\level\particle\GenericParticle;
use pocketmine\math\Vector3;

class PlayerParticles extends PluginBase{
	private $listener; 
	private $config;
	private $models = [];
	private $registeredDefaults = [];
	
	/**
	  * You should check this against your version either with your own implementation
	  * or with @link{$this->isCompatible}
	  * (This only tracks changes to non @internal stuff)
	  * If C changes:
	  * C.x.x Breaking changes, disable your plugin with an error message or disable any PP API usage.
	  * x.C.x Feature additions, usually not breaking. (Use this if you require certain new features)
	  * x.x.C BugFixes on API related functions, not breaking.
	  */
	const API_VERSION = "1.0.0-InDev";

	private static $defaultModels = [
		"Wing" => "wing.yml",
		"Helix" => "helix.yml"
	];

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, []);
		if($this->config->get("ConfigVersion") != 0.1){
			$this->config->set('lang', 'eng');
			$this->config->set('modeldatapath', "/models");
			$this->config->set('debug', true);
			$this->config->set('ConfigVersion', 0.1);
		}
		$this->config->save();
		Utils::init($this, $this->config->get('debug'), "[PlayerParticles]");
		$this->initDefaults($this->config->get("modeldatapath")."/defaults");
		$this->lookForUserFiles($this->getDataFolder().$this->config->get("modeldatapath"));
		$this->listener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($this->listener, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RenderManager($this), 5);
	}
    
	/**
	  * For extension plugins to test if they are compatible with the version
	  * of PP installed.
	  * 
	  * @param string $apiVersion The API version your plugin was last tested on.
	  *
	  * @return bool Indicates if your plugin is compatible.
	  */
	public function isCompatible(string $apiVersion): bool{
		$extensionApiVersion = explode(".", $apiVersion);
		$myApiVersion = explode(".", self::API_VERSION);
		if($extensionApiVersion[0] !== $myApiVersion[0]){
			return false;
		}
		if($extensionApiVersion[1] > $myApiVersion[1]){
			return false;
		}
		return true;
	}
	
	/**
      * @param string $registeredName The registeredName of your wanted model
	  *
	  * @return Model|null
	  */
	public function getModel(string $registeredName): ?Model{
		if(isset($this->models[$registeredName])){
			return $this->models[$registeredName];
		}
		return null;
	}
	
	/**
	  * @param string $name The name of your wanted model(s)
	  * 
	  * Note: You should always use different ways to get the model because this is
	  * quite resource intensive and can return multiple results.
	  *
	  * @return array [$registeredName => Model]
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
	  * Note: If a model with the same name already exists, it will be saved with a number at
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
			while(!$foundFreeName){
				$name = $model->getName().$i;
				if(!isset($this->models[$name])){
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
	
	/**
      * @param string $registeredName The registeredName of the model
	  *
	  * @return bool Success
	  */
	public function unregisterModel(string $registeredName): bool{
		if(isset($this->models[$registeredName])){
			unset($this->models[$registeredName]);
			return true;
		}
		return false;
	}
	
	/**
	  * Renders $model for $pos
	  *
	  * @param Location $pos The location at which particles should be spawned
	  * @param Model $model The model that should be rendered
	  *
	  * @return bool Success
	  */
	public function render(Location $pos, Model $model, array $additionalData = []): bool{
		set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext){
			Utils::debug(print_r($errcontext, true));
		    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
		try{
			$px = $pos->getX();
			$pz = $pos->getZ();
			$py = $pos->getY();
			$level = $pos->getLevel();
			
			$yOffset = 2;
			switch($model->getModelType()){
				case "helix":
					$yOffset = 0;
					
					$h = 2;
					$yi = 0.02;
					$t = 0.25;
					$ti = 0.01;
					$res = 20;
					$y = $py + $yOffset;
					$rot = $additionalData[0];
					$rotRad = $rot * self::DEG_TO_RAD;
					$cos = cos($rotRad);
					$sin = sin($rotRad); 
					for($yaw = 0, $cy = $y; $cy < $y + $h; $yaw += (M_PI * 2) / $res, $cy += $yi, $t += $ti){
						$diffx = -sin($yaw) * $t;
						$diffz = cos($yaw) * $t;
						$rx = $diffx * $cos + $diffz * $sin;
						$rz = -$diffx * $sin + $diffz * $cos;
						$fx = $px + $rx;
						$fz = $pz + $rz;
						$particleObject = new GenericParticle(new Vector3($fx, $cy, $fz), 7);
						$level->addParticle($particleObject);
					}
				break;
				case "headcircle":
					$y = $py + $yOffset;
					$ri = 30;
					$t = 0.6;
					
					$rot = $model->getRuntimeData("rot");
					if($rot === null){
						$rot = 0;
					}
					$rot += $ri;
					if($rot > 360){
						$rot = $ri;
					}
					echo("rot::$rot");
					$model->setRuntimeData("rot", $rot);
					
					$rotRad = $rot * self::DEG_TO_RAD;
					$rx = -sin($rotRad) * $t;
					$rz = cos($rotRad) * $t;
					$fx = $px + $rx;
					$fz = $pz + $rz;
					$particleObject = new GenericParticle(new Vector3($fx, $y, $fz), 17);
					$level->addParticle($particleObject);
				break;
				case "cone":
					$t = 1;
					$s = 5;
					$d = 10;
					$v = 0.25;
					$p = 0.25;
					$y = $pos->getY();
					$dcpi = abs($t - $s) / ($d / $v);
					for($iy = $y + $d; $y <= $iy; $iy -= $v){
						$t -= $dcpi;
						$ppc = round((2 * M_PI * $t) / $p);
						for($yaw = 0; $yaw <= M_PI * 2; $yaw += (M_PI * 2) / $ppc){
							$diffx = -sin($yaw) * $t;
							$diffz = cos($yaw) * $t;
							$fx = $px + $diffx;
							$fz = $pz + $diffz;
							$particleObject = new GenericParticle(new Vector3($fx, $iy, $fz), 7);
							$level->addParticle($particleObject);
						}
					}
				break;
				case "back":
					$y = $py + $yOffset;
					$layout = $model->getModelData();
					$yaw = $pos->getYaw(); /* 0-360 DEGREES */
					$yaw -= 45;
					$sp = $model->getSpacing();
					$yawRad = ($yaw * -1 * self::DEG_TO_RAD); /* RADIANS - Don't ask me why inverted! */
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
							$b = $yaw - 45;
							$bx = cos($b * self::DEG_TO_RAD) * $sp;
							$bz = sin($b * self::DEG_TO_RAD) * $sp;
							$r = $yaw + 225;
							$cx = cos($r * self::DEG_TO_RAD) * $amb;
							$cz = sin($r * self::DEG_TO_RAD) * $amb;
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
			restore_error_handler();
			return true;
		}catch(\Throwable $t){
			Utils::emergency("Failed to render Model '".$model->getName()."':");
			$this->getLogger()->logException($t);
			restore_error_handler();
			return false;
		}
	}
	
	public function getRegisteredModels(): array{
		return $this->models;
	}
	
	public function getDefaultModels(): array{
		return $this->registeredDefaults;
	}
	
	public function createModelFromData(array $data, $name = null): Model{ //Add ?string typehint PHP 7.1
		if(isset($data['name'])){
			$rname = $data['name'];
		}elseif(is_string($name)){
			$rname = $name;
			$data['name'] = $name;
		}else{
			Utils::critical("Cannot create Model from Data: No name found at all!");
			$e = new \Exception("ERR_901");
			Utils::debug($e->getTraceAsString());
		}
		if(isset($data['modeltype'])){
			if(!is_string($data['modeltype'])){
				Utils::notice("Model '".$this->name."': Key 'modeltype' exists, but is not string, ignoring.");
				$data['modeltype'] = "back";
			}
		}else{
			Utils::notice("Model '".$rname."': Required key 'modeltype' does not exist, using default.");
			$data['modeltype'] = "back";
		}
		if($name !== null){
			$rname = $name;
		}
		switch($data['modeltype']){
			case "back":
				$model = new Model2DMap($data, $rname);
			break;
			default:
				$model = new Model($data, $rname); #will probably result in an invalid model, worth a try though
		}
		return $model;
	}
	
	public function lookForUserFiles(string $path){
		$rdi = new \RecursiveDirectoryIterator($path);
		$rii = new \RecursiveIteratorIterator($rdi);
		$ymlFiles = new \RegexIterator($rii, '/^.+\.yml$/i', \RegexIterator::GET_MATCH);
		foreach($ymlFiles as $ymlFile){
			var_dump($ymlFile);
		}
	}
	
	/** @internal */
	private function initDefaults(string $path){
		foreach(self::$defaultModels as $name => $fileName){
			$res = $this->getResource($fileName);
			if($res === null){
				Utils::critical("FATAL! No resource for '".$name."' found, was expecting '".$fileName."' in resources!");
				continue;
			}elseif(is_resource($res)){
				fclose($res);
			}else{
				Utils::critical("PluginBase->getResource returned no resource and not null. Aborting loading '".$name."'!");
				continue;
			}
			$this->saveResource($fileName);
			@mkdir($this->getDataFolder().$path, 0777, true);
			rename($this->getDataFolder().$fileName, $this->getDataFolder().$path."/".$fileName);

			try{
				$cfg = new Config($this->getDataFolder().$path."/".$fileName, Config::YAML);
			}catch(\Throwable $t){
				$this->getLogger()->logException($t);
			}
			if($cfg->check()){
				if($registerName = $this->registerModel($this->createModelFromData($cfg->getAll(), $name)) !== ""){
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