<?php

namespace robske_110\PlayerParticles\Model;

use robske_110\PlayerParticles\PlayerParticles;
use robske_110\Utils\Utils;
use robske_110\PlayerParticles\Model\Model;

class Model2DMap extends Model{

	const CENTER_STATIC = 0;
	const CENTER_DYNAMIC = 1;

	private $map;
	private $centerMode;
	private $spacing = 0.2;
	
	private $strlenMap = [];
	private $particleMap = [];

	public function __construct(PlayerParticles $main, array $data, $name = null){
		if(parent::__construct($main, $data, $name, "back") === false){
			Utils::debug("Model '".isset($data['name']) ? $data['name'] : $name."': Exiting out of subclass 2DMapModel");
			return false;
		}
		if(is_string($msg = self::checkIntegrity($data, $name, true))){ #recover from missing/invalid model data
			Utils::critical("Model '".isset($data['name']) ? $data['name'] : $name."' could not be loaded: ".$msg."!");
			return false;
		}
		$this->map = explode("\n", $data['model']);
		switch($data['centermode']){
			default:
				Utils::notice("Model '".$this->getName()."': CenterMode '".$data['centermode']."' not known, using default!");
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
			foreach($this->map as $key => $model){
				$this->strlenMap[$key] = strlen($this->map[$key]);
			}
		}
		if(isset($data['spacing'])){
			if(is_int($data['modeltype'])){
				$this->spacing = $data['spacing'];
			}else{
				Utils::notice("Model '".$this->getName()."': Key 'spacing' exists, but is not int, ignoring!");
			}
		}else{
			Utils::debug("Model '".$this->getName()."': Key 'spacing' does not exist, using default.");
		}
	}
	
	/**
	  * Can be used to check the basic integrity of $data, provide null for $name if not applicable
	  * You may want to use this if you provide user entered data. 
	  *
	  * @param array       $data   The data array to be checked
	  * @param null|string $name   The name to be checked against (provide null if not applicable)
	  * @param bool        $onlyMe INTERNAL! (May be removed anytime without API bump) 
	  *
	  */
	public static function checkIntegrity(array $data, $name, bool $onlyMe = false){
		$stringKeys = ['model', 'centermode'];
		foreach($stringKeys as $stringKey){
			if(!isset($data[$stringKey])) return "Required key '".$stringKey."' not found";
			if(!is_string($data[$stringKey])) return "Key '".$stringKey."' is not string";
		}
		if($onlyMe){
			return true;
		}
		return parent::checkIntegrity($data, $name);
	}
    
	public function get2DMap(): array{
		return $this->map;
	}
	
	public function getStrlenMap(): array{
		return $this->strlenMap;
	}
	
	public function getCenterMode(): int{
		return $this->centerMode;
	}
	
	public function getSpacing(): int{
		return $this->spacing;
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!