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
use robske_110\PlayerParticles\Render\Renderer;

class PlayerParticles extends PluginBase{
	private $listener; 
	private $config;
	private $models = [];
	private $renderer;
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
		if($this->config->get("ConfigVersion") != 0.15){
			$this->config->set('lang', 'eng');
			$this->config->set('modeldatapath', "models");
			$this->config->set('debug', true);
			$this->config->set('ConfigVersion', 0.15);
		}
		$this->config->save();
		Utils::init($this, $this->config->get('debug'), "[PlayerParticles]");
		$this->initDefaults($this->config->get("modeldatapath")."/defaults");
		$this->lookForUserFiles($this->getDataFolder().$this->config->get("modeldatapath"));
		$this->listener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($this->listener, $this);
		$this->renderer = new Renderer($this);
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
	  * @return Renderer
	  */
	public function getRenderer(): Renderer{
		return $this->renderer;
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
			Utils::critical("Cannot create Model from Data: No name could be determined!");
			$e = new \Exception("No name found!");
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
			try{
				$cfg = new Config($ymlFile[0], Config::YAML);
			}catch(\Throwable $t){
				$this->getLogger()->logException($t);
			}
			if($cfg->check()){
				if($registerName = $this->registerModel($this->createModelFromData($cfg->getAll())) === ""){
					Utils::critical("Failed to register Model '".$name."'! Did you create it incorrectly?");
				}
			}else{
				Utils::critical("Failed to load config for Model '".$name."'! Check for parse errors above.");
			}
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