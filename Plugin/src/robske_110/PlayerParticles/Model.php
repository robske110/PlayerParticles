<?php

namespace robske_110\PlayerParticles;

use robske_110\PlayerParticles\PlayerParticles;
use robske_110\Utils\Utils;

class Model{

	const CENTER_STATIC = 0;
	const CENTER_DYNAMIC = 1;

	private $name = "";
	private $perm;
	private $model;
	private $centerMode;
	private $modelType = "back";

	public function __construct(PlayerParticles $main, array $data, $name = null){
		if($name !== null && !is_string($name)){
			Utils::critical("Model '".$name."' could not be loaded: Name must be null or string!");
			$main->unregisterModel($this);
			return;
		}
		if(is_string($msg = self::checkIntegrity($data, $name))){ #recover from invalid model data
			Utils::critical("Model '".$name."' could not be loaded: ".$msg."!");
			$main->unregisterModel($this);
			return;
		}
		$this->name = $data['name'];
		$this->perm = $data['permgroup'];
		$this->model = explode("\n", $data['model']);
		switch($data['centermode']){
			default:
				Utils::notice("CenterMode '".$data['centermode']."' not known, using default!");
			case "static":
			case "total":
			case "all":
			case "max":
				$this->centerMode = self::CENTER_STATIC;
			break;
			case "dynamic":
			case "invidual":
			case "each":
				$this->centerMode = self::CENTER_DYNAMIC;
			break;
		}
		if($this->centerMode == self::CENTER_STATIC){
			foreach($this->model as $key => $model){
				$this->model[$key] = strlen($this->model[$key]).$this->model[$key];
			}
		}
		if(isset($data['modeltype'])){
			if(is_string($data['modeltype'])){
				$this->modelType = $data['modeltype'];
			}else{
				Utils::notice("Model '".$name."': Key modeltype exists, but is not string, ignoring!");
			}
		}
	}
    
	public static function checkIntegrity(array $data, $name){
		if($name !== null){
			if($name !== $data['name']){
				return "Expected '".$name."' got '".$data['name']."' Did you modify the name of an example? Expected name must be the same as name in data, or null";
			}
		}
		$stringKeys = ['permgroup', 'model', 'centermode'];
		foreach($stringKeys as $stringKey){
			if(!isset($data[$stringKey])) return "Required key '".$stringKey."' not found";
			if(!is_string($data[$stringKey])) return "Key '".$stringKey."' is not string";
		}
	}
	
	public function getModelData(): array{
		return $this->model;
	}
	
	public function getName(): string{
		return $this->name;
	}
	
	public function getModelType(): string{
		return $this->modelType;
	}
	
	public function getCenterMode(): int{
		return $this->centerMode;
	}
	
	public function getPerm(): string{
		return $this->perm;
	}
	
	/** @TODO */
	public function canBeUsedByPlayer(Player $player): bool{
		return true;
	}
	
	/**DEBUG*/
	public function __destruct(){
		echo("GC got Model ".$this->name." !");
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!