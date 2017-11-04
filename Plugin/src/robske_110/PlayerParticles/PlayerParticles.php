<?php

namespace robske_110\PlayerParticles;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerLoginEvent;

use robske_110\PlayerParticles\Model\Model;
use robske_110\PlayerParticles\Model\ModelManager;
use robske_110\PlayerParticles\Render\RenderManager;
use robske_110\Utils\Utils;
#use robske_110\Utils\Translator;
use robske_110\PlayerParticles\Render\Renderer;

class PlayerParticles extends PluginBase{
	/** @var Config */
	private $config;
	
	/** @var EventListener */
	private $listener;
	/** @var Renderer */
	private $renderer;
	/** @var RenderManager */
	private $renderManager;
	/** @var PlayerManager */
	private $playerManager;
	
	/**
	 * You should check this against your version either with your own implementation
	 * or with @link{$this->isCompatible}
	 * (This only tracks changes to non @internal marked stuff)
	 * If C changes:
	 * C.x.x Breaking changes, disable your plugin with an error message or disable any PP API usage.
	 * x.C.x Feature additions, usually not breaking. (Use this if you require certain new features)
	 * x.x.C BugFixes on API related functions, not breaking.
	 */
	const API_VERSION = "0.0.0-InDev";
	
	/** @var Model[] */
	private $models = [];
	/** @var Model[] */
	private $registeredDefaults = [];
	/** @var array */
	private static $defaultModels = [
		"Wing" => "wing.yml",
		"Helix" => "helix.yml"
	];

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, []);
		if($this->config->get("ConfigVersion") != 0.20){
			$this->config->set('lang', 'eng');
			$this->config->set('modeldatapath', "models");
			$this->config->set('main-tickrate', 5);
			$this->config->set('hide-during-movement', true);
			$this->config->set('hide-during-movement-cooldown', 5);
			$this->config->set('debug', true);
			$this->config->set('ConfigVersion', 0.20);
		}
		$this->config->save();
		Utils::init($this, $this->config->get('debug'), "[PlayerParticles]");
		
		ModelManager::init();
		$this->registerDefaultModels($this->config->get("modeldatapath")."/defaults");
		$this->registerUserModels($this->getDataFolder().$this->config->get("modeldatapath"));
		
		$this->listener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($this->listener, $this);
		$this->renderer = new Renderer($this);
		$this->playerManager = new PlayerManager($this);
		$this->renderManager = new RenderManager($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->renderManager, $this->config->get("main-tickrate"));
		
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->listener->onJoin(new PlayerLoginEvent($player, ""));
		}
	}

	public function onDisable(){
		Utils::close();
	}
	
	/**
	 * For extension plugins to test if they are compatible with the version
	 * of PP installed.
	 *
	 * @param string $apiVersion The API version your plugin was last tested on.
	 *
	 * @return bool Indicates whether your plugin is compatible.
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
	 * @return Config
	 */
	public function getConfig(): Config{
		return $this->config;
	}
	
	/**
	 * @return Renderer
	 */
	public function getRenderer(): Renderer{
		return $this->renderer;
	}
	
	/**
	 * @return RenderManager
	 */
	public function getRenderManager(): RenderManager{
		return $this->renderManager;
	}
	
	/**
	 * @return PlayerManager
	 */
	public function getPlayerManager(): PlayerManager{
		return $this->playerManager;
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
		if($model->isInvalid()){
			Utils::debug("Attempted to register an invalid Model!");
			return "";
		}

		if(isset($this->models[$model->getName()])){
			Utils::debug("Model '".$model->getName()."' already exists, incrementing name.");
			$i = 1;
			while(true){
				$name = $model->getName().$i;
				if(!isset($this->models[$name])){
					$this->models[$name] = $model;
					return $name;
				}
				$i++;
			}
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
	 * Returns all registeredModels, including defaults. The key is the registeredName/ID and the value is the Model
	 * Object.
	 *
	 * @return array
	 */
	public function getRegisteredModels(): array{
		return $this->models;
	}
	
	/**
	 * Returns all registeredDefaults with the key being the actual registeredName/ID and the value is the name which
	 * is equal to the index name in @link{self::$defaultModesl} for that specific default Model.
	 *
	 * @return array
	 */
	public function getDefaultModels(): array{
		return $this->registeredDefaults;
	}
	
	/**
	 * Creates a Model object from data.
	 *
	 * @param array $data
	 * @param null|string $name
	 *
	 * @return null|Model
	 */
	public function createModelFromData(array $data, ?string $name = null): ?Model{
		if(isset($data['name'])){
			$rName = $data['name'];
		}elseif(is_string($name)){
            $rName = $name;
			$data['name'] = $name;
		}else{
			Utils::critical("Cannot create Model from Data: No name could be determined!");
			$e = new \Exception("No name found!");
			Utils::debug($e->getTraceAsString());
			return null;
		}
		if(isset($data['modeltype'])){
			if(!is_string($data['modeltype'])){
				Utils::notice("Model '".$rName."': Key 'modeltype' exists, but is not string, ignoring.");
				$data['modeltype'] = "back";
			}
		}else{
			Utils::notice("Model '".$rName."': Required key 'modeltype' does not exist, using default.");
			$data['modeltype'] = "back";
		}
		if(($className = ModelManager::getClassNameForModelType($data['modeltype'])) !== null){
            $model = new $className($data, $rName);
        }else{
            $model = new Model($data, $rName); #will probably result in an invalid model, worth a try though
        }
		/*if($model->isInvalid()){
			return null;
		}*/
		return $model;
	}
	
	/**
	 * @internal
	 * @param string $path
	 */
	public function registerUserModels(string $path){
		$rdi = new \RecursiveDirectoryIterator($path);
		$rii = new \RecursiveIteratorIterator($rdi);
		$ymlFiles = new \RegexIterator($rii, '/^.+\.yml$/i', \RegexIterator::GET_MATCH);
		foreach($ymlFiles as $ymlFile){
			if(strpos($ymlFile[0], $path."/defaults") !== false){
				continue;
			}
			try{
				$cfg = new Config($ymlFile[0], Config::YAML);
			}catch(\Throwable $t){
				$this->getLogger()->logException($t);
				Utils::critical("Failed to load config for Model at '".$ymlFile[0]."'!");
				continue;
			}
			if($cfg->check()){
				if($registerName = $this->registerModel($this->createModelFromData($cfg->getAll())) === ""){
					Utils::critical("Failed to register Model at '".$ymlFile[0]."'! Did you create it incorrectly?");
				}
			}else{
				Utils::critical("Failed to load config for Model at '".$ymlFile[0]."'! Check for parse errors above.");
			}
		}
	}

    /**
     * @internal
     * @param string $path
     */
    private function registerDefaultModels(string $path){
		foreach(self::$defaultModels as $name => $fileName){
			$res = $this->getResource($fileName);
			if($res === null){
				Utils::critical("FATAL! No resource for '".$name."' found, was expecting '".$fileName."' in resources!");
				continue;
			}elseif(is_resource($res)){
				fclose($res);
			}else{
				Utils::critical("PluginBase->getResource(".$fileName.") returned no resource and not null. Aborting loading '".$name."'!");
				continue;
			}
			$this->saveResource($fileName);
			@mkdir($this->getDataFolder().$path, 0777, true);
			rename($this->getDataFolder().$fileName, $this->getDataFolder().$path."/".$fileName);

			try{
				$cfg = new Config($this->getDataFolder().$path."/".$fileName, Config::YAML);
			}catch(\Throwable $t){
				$this->getLogger()->logException($t);
				Utils::critical("Failed to load config for default Model '".$name."'!");
				continue;
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