<?php

namespace robske_110\PlayerParticles\Model;

use robske_110\PlayerParticles\PlayerParticles;
use robske_110\Utils\Utils;

use pocketmine\level\particle\Particle;

class Model{

	const RTM_DATA_TICK_ROT = 'eaca90748e20df12d32e';
	const DEFAULT_PARTICLE_TYPE = [Particle::TYPE_FLAME, null];

	private $name = "";
	private $modelType = "generic";
	private $particleType = null;
	private $perm;
	
	private $child = null;
	
	private $runtimeData = [];
	private $active = true;

	public function __construct(array $data, $name = null, $child = null){
		if($child !== null){
			$this->child = $child;
			$forcedID = $child->getID();
		}else{
			$forcedID = null;
		}
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
		if($this->hasParticleType()){
			if(isset($data['particle'])){
				$input = $data['particle'];
				$finalParticle = [null, null];
				if(is_int($input)){
					$finalParticle[0] = $input;
				}else{
					if(strpos($input, ":") !== false){
						$inputa = explode($input, ":");
						if(count($inputa) > 2){
							Utils::notice("Model '".$this->name."': Attribute 'particle': Has more than 2 sections, any above 2 will be ignored.");
						}
						if(is_int($inputa[0])){
							$finalParticle[0] = $inputa[0];
						}else{
							$finalParticle[0] = self::getParticleIDbyName($inputa[0]);
							Utils::notice("Model '".$this->name."': Attribute 'particle': Section1: The Particle with the name ".$inputa[0]." could not be found! Please use the Particle constant names.");
						}
						if(is_string($inputa[1])){
							if(strpos($inputa[1], ",") !== false){
								$inputasa = explode($input, ",");
								if(count($inputasa) > 4){
									Utils::notice("Model '".$this->name."': Attribute 'particle': Section2: Too many sections. Ignoring.");
								}
								if(count($inputasa) < 3){
									Utils::notice("Model '".$this->name."' could not be loaded: Attribute 'particle': Section2: Must have at least 3 sections.");
									return false;
								}
								$r = $inputasa[0];
								$g = $inputasa[1];
								$b = $inputasa[2];
								$a = isset($inputasa[3]) ? $inputasa[3] : 255;
								$finalParticle[1] = (($a & 0xff) << 24) | (($r & 0xff) << 16) | (($g & 0xff) << 8) | ($b & 0xff);
							}elseif(ctype_xdigit($inputa[1])){
								$finalParticle[1] = hexdec($inputa[1]);
							}else{
								Utils::critical("Model '".$this->name."' could not be loaded: Failure during parsing of 'particle' key: Unexpected Value for extraData");
								return false;
							}
						}elseif(is_int($inputa[1])){
							$finalParticle[1] = $inputa[1];
						}else{
							Utils::critical("Model '".$this->name."': WWWWTTTTFFF?? ERR_904");
						}
					}else{
						$finalParticle[0] = self::getParticleIDbyName($input);
						if($finalParticle[0] == null){
							Utils::notice("Model '".$this->name."': Attribute 'particle': The Particle with the name ".$input." could not be found! Please use the Particle constant names.");
						}
					}
				}
				if($finalParticle[0] === null){
					$finalParticle[0] = Particle::TYPE_FLAME; 
				}
				$this->particleType = $finalParticle;
			}else{
				Utils::notice("Model '".$this->name."': Key 'particle' does not exist, using default.");
				$this->particleType = self::DEFAULT_PARTICLE_TYPE;
			}
		}
		$this->perm = $data['permgroup'];
	}
    
	public static function getParticleIDbyName(string $name){ #PHP 7.1: add ?int
		if(defined("Particle::".$name)){
			return constant("Particle::".$name);
		}else{
			return null;
		}
	}
	
	/**
	  * Can be used to check the basic integrity of $data, provide null for $name if not applicable
	  * You may want to use this if you provide user entered data. 
	  *
	  * @param array       $data   The data array to be checked
	  * @param null|string $name   The name to be checked against (provide null if not applicable)
	  *
	  */
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
	
	/** @internal */
	public function hasParticleType(): bool{
		if($this->child !== null){
			return $this->child->hasParticleType();
		}
		return true;
	}
	
	/** @internal */
	public function needsTickRot(): bool{
		return false;
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