<?php

namespace robske_110\PlayerParticles\Model;

use robske_110\Utils\Utils;

class Model2DMap extends Model{

	const CENTER_STATIC = 0;
	const CENTER_DYNAMIC = 1;

	/** @var array  */
	private $map;
	/** @var int */
	private $centerMode;
	/** @var float|int  */
	private $spacing = 0.25;
	/** @var float|int  */
	private $backwardsOffset = 0.25;

	/** @var array  */
	private $strlenMap = [];
	/** @var array @todo */
	private $particleMap = [];

	public function __construct(array $data, $name = null){
		if(parent::__construct($data, $name, $this) === false){
			Utils::debug("Model '".isset($data['name']) ? $data['name'] : $name."': Exiting out of subclass 2DMapModel");
			return false;
		}
		if(is_string($msg = self::checkIntegrity($data, $name, true))){ #recover from missing/invalid model data
			Utils::critical("Model '".isset($data['name']) ? $data['name'] : $name."' could not be loaded: ".$msg."!");
			return false;
		}
		$this->map = explode("\n", $data['model']);
		switch($data['centermode']){
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
			default:
				Utils::notice("Model '".$this->getName()."': CenterMode '".$data['centermode']."' not known, using default!");
			break;
		}
		if($this->centerMode == self::CENTER_STATIC){
			foreach($this->map as $key => $model){
				$this->strlenMap[$key] = strlen($this->map[$key]);
			}
		}
		if(isset($data['particles'])){
			if(is_array($data['particles'])){
				$fail = false;
				foreach($data['particles'] as $letter => $particle){
					if(strlen($letter) > 1){
						$failmsg = "Identifier is supposed to be one char long.";
						$fail = true;
					}
					$letter = strtoupper($letter);
					if($letter === "X"){
						$failmsg = "Don't use SPACE (X) as an identifier in ParticleMap";
						$fail = true;
					}
					if(isset($this->particleMap[$letter])){
						$failmsg = "You cannot use an Identifier twice in ParticleMap";
						$fail = true;
					}
					if($fail){
						Utils::critical("Model '".$this->getName()."': ParticleMap could not be loaded: ".$failmsg);
						$this->particleMap = [];
						break;
					}
					$this->particleMap[$letter] = $this->parseParticle($particle, "ParticleMap identifier ".$letter);
					if($this->particleMap[$letter] === null){
						Utils::critical("Model '".$this->getName()."': ParticleMap could not be loaded: Parsing particle fail at ParticleMap ident ".$letter); //TODO
						$this->particleMap = [];
						break;
					}
				}
			}
		}
		foreach($this->map as $line => $layer){
			for($verticalPos = strlen($layer) - 1; $verticalPos >= 0; $verticalPos--){
				if($layer[$verticalPos] !== "X" && $layer[$verticalPos] !== "P"){
					if(!isset($this->particleMap[$layer[$verticalPos]])){
						Utils::critical("Model '".$this->getName()."' could not be loaded: Layout/Map contains unknown identifiers!");
						return false;
					}
				}
			}
		}
		if(isset($data['spacing'])){
			if(is_numeric($data['spacing'])){
				$this->spacing = $data['spacing'];
			}else{
				Utils::notice("Model '".$this->getName()."': Key 'spacing' exists, but is not int/float, ignoring!");
			}
		}else{
			Utils::debug("Model '".$this->getName()."': Key 'spacing' does not exist, using default.");
		}
		if(isset($data['backwardsOffset'])){
			if(is_numeric($data['backwardsOffset'])){
				$this->backwardsOffset = $data['backwardsOffset'];
			}else{
				Utils::notice("Model '".$this->getName()."': Key 'backwardsOffset' exists, but is not a valid number, ignoring!");
			}
		}else{
			Utils::debug("Model '".$this->getName()."': Key 'backwardsOffset' does not exist, using default.");
		}
	}
	
	/**
	 * Can be used to check the basic integrity of $data, provide null for $name if not applicable
	 * You may want to use this if you provide user entered data.
	 *
	 * @param array       $data   The data array to be checked
	 * @param null|string $name   The name to be checked against (provide null if not applicable)
	 * @param bool        $onlyMe @internal (May be removed anytime without API bump)
	 *
     * @return bool|string
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
    
	public static function getID(): string{
		return "back";
	}
	
	public function get2DMap(): array{
		return $this->map;
	}
	
	public function getStrlenMap(): array{
		return $this->strlenMap;
	}
	
	public function getParticleMap(): array{
		return $this->particleMap;
	}
	
	public function getBackwardsOffset(): float{
		return $this->backwardsOffset;
	}
	
	/** @internal */
	public function hasParticleType(): bool{
		return false;
	}
	
	/** @internal */
	public function needsTickRot(): bool{
		return false;
	}
	
	public function getCenterMode(): int{
		return $this->centerMode;
	}
	
	public function getSpacing(): float{
		return $this->spacing;
	}
}