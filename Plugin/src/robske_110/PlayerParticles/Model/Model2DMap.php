<?php

namespace robske_110\PlayerParticles\Model;

use robske_110\Utils\Utils;

class Model2DMap extends Model{

	const CENTER_STATIC = 0;
	const CENTER_DYNAMIC = 1;

	const PARAMS = [
		"model" => [Model::PARAMS_TYPE_STRING, null, true],
		"centermode" => [Model::PARAMS_TYPE_STRING, null, true],
		"spacing" => [Model::PARAMS_TYPE_NUMERIC, "spacing", false],
		"backwardsoffset" => [Model::PARAMS_TYPE_NUMERIC, "backwardsOffset", false],
	];

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
	/** @var array */
	private $particleMap = [];

	public function __construct(array $data, ?string $name = null){
		parent::__construct($data, $name, $this->getID());
		if($this->isInvalid()){
			return;
		}
		if(is_array($result = $this->processOptions($data, self::PARAMS))){
			foreach($result as $optionVarName => $optionData){
				$this->$optionVarName = $optionData;
			}
		}elseif(is_string($result)){
			$this->modelLoadFail($result."!");
			$this->isInvalid = true;
			return;
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
				$this->modelMessage("CenterMode '".$data['centermode']."' not known, using default!");
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
						$this->modelLoadFail("ParticleMap could not be loaded: ".$failmsg);
						$this->particleMap = [];
						break;
					}
					$this->particleMap[$letter] = $this->parseParticle($particle, "ParticleMap identifier ".$letter);
					if($this->particleMap[$letter] === null){
						$this->modelLoadFail(
							"ParticleMap could not be loaded:".
							"Parsing particle fail at ParticleMap identifier '".$letter."'!"
						);
						$this->particleMap = [];
						break;
					}
				}
			}
		}
		foreach($this->map as $line => $layer){
			$layer = str_replace(" ", "", $layer);
			$this->map[$line] = $layer;
			for($verticalPos = strlen($layer) - 1; $verticalPos >= 0; $verticalPos--){
				if($layer[$verticalPos] !== "X" && $layer[$verticalPos] !== "P"){
					if(!isset($this->particleMap[$layer[$verticalPos]])){
						$this->modelLoadFail(
							"Layout/Map contains unknown identifier(s): In line ".
							$line.": '".$layer[$verticalPos]."'"
						);
						$this->isInvalid = true;
						return;
					}
				}
			}
		}
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