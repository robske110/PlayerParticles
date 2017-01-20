<?php

namespace robske_110\PlayerParticles\Model;

use robske_110\PlayerParticles\PlayerParticles;
use robske_110\Utils\Utils;

class Model{

	const CENTER_STATIC = 0;
	const CENTER_DYNAMIC = 1;

	private $name = "";
	private $perm;
	private $model;
	private $centerMode;
	private $modelType = "generic";
	private $spacing = 0.2;
	
	private $strlenMap = [];
	private $runtimeData = [];

	public function __construct(PlayerParticles $main, array $data, $name = null, $forcedID = null){
		if($name !== null && !is_string($name)){
			Utils::critical("Model '".isset($data['name']) ? $data['name'] : ""."' could not be loaded: Name must be null or string!");
			return false;
		}
		if(is_string($msg = self::checkIntegrity($data, $name))){ #recover from missing/invalid model data
			Utils::critical("Model '".isset($data['name']) ? $data['name'] : $name."' could not be loaded: ".$msg."!");
			return false;
		}
		$this->name = $data['name'];
		if(isset($data['modeltype'])){
			if(is_string($data['modeltype'])){
				if($forcedID !== null && ($data['modeltype'] !== $forcedID)){
					Utils::critical("Model '".$this->name."' could not be loaded: Wrong modeltype for the subclass!");
					return false;
				}
				$this->modelType = $data['modeltype'];
			}else{
				Utils::notice("Model '".$this->name."': Key 'modeltype' exists, but is not string, ignoring.");
			}
		}else{
			Utils::notice("Model '".$this->name."': Key 'modeltype' does not exist, using default.");
		}
		$this->perm = $data['permgroup'];
	}
    
	public static function checkIntegrity(array $data, $name){
		$stringKeys = ['permgroup', 'name'];
		foreach($stringKeys as $stringKey){
			if(!isset($data[$stringKey])) return "Required key '".$stringKey."' not found";
			if(!is_string($data[$stringKey])) return "Key '".$stringKey."' is not string";
		}
		if($name !== null){
			if($name !== $data['name']){
				return "Expected '".$name."' got '".$data['name']."' Did you modify the name of an example? Expected name must be the same as name in data, or null";
			}
		}
		return true;
	}
	
	public function getName(): string{
		return $this->name;
	}
	
	public function getModelType(): string{
		return $this->modelType;
	}
	
	public function getPerm(): string{
		return $this->perm;
	}
	
	public function getRuntimeData(string $key){
		if(isset($this->runtimeData[$key])){
			return $this->runtimeData[$key];
		}
		return null;
	}
	
	public function setRuntimeData(string $key, $data){
		$this->runtimeData[$key] = $data;
	}
	
	/** @TODO */
	public function canBeUsedByPlayer(Player $player): bool{
		return true;
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!