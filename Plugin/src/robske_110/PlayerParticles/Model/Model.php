<?php

namespace robske_110\PlayerParticles\Model;

use robske_110\Utils\Utils;

use pocketmine\Player;
use pocketmine\level\particle\Particle;

class Model{

	const DEFAULT_PARTICLE_TYPE = [Particle::TYPE_FLAME, null];
	
	const PARAMS_TYPE_STRING = 0;
	const PARAMS_TYPE_NUMERIC = 1;
	
	const PARAMS = [
		"name" => [Model::PARAMS_TYPE_STRING, "name", true],
		"modeltype" => [Model::PARAMS_TYPE_STRING, "modelType", true],
		"permgroup" => [Model::PARAMS_TYPE_STRING, "perm", true],
	];

	/** @var string  */
	private $name = "";
	/** @var string */
	private $modelType = "";
	/** @var array|null  */
	private $particleType = null;
	/** @var string */
	private $perm = "";
	
	/** @var array */
	private $runtimeData = [];
	
	/** @var bool */
	protected $isInvalid = false;

	public function __construct(array $data, ?string $name = null, ?string $forcedID = null){
		if(is_array($result = $this->processOptions($data, self::PARAMS))){
			foreach($result as $optionVarName => $optionData){
				$this->$optionVarName = $optionData;
			}
		}elseif(is_string($result)){
			Utils::critical("Model '".isset($data['name']) ? $data['name'] : $name."' could not be loaded: ".$result."!");
			$this->isInvalid = true;
			return;
		}
		if($forcedID !== null && $this->modelType !== $forcedID){
			$this->modelLoadFail("Wrong modeltype for the subclass!");
			$this->isInvalid = true;
			return;
		}
		if(isset($data['particle'])){
			$this->particleType = $this->parseParticle($data['particle'], "Attribute 'particle'");
			if($this->particleType == null){
				$this->isInvalid = true;
				return;
			}
		}else{
			$this->modelMessage("Key 'particle' does not exist, using default.", Utils::LOG_LVL_DEBUG);
			$this->particleType = self::DEFAULT_PARTICLE_TYPE;
		}
	}
 
	public function modelMessage(string $msg, int $logLevel = Utils::LOG_LVL_NOTICE){
		Utils::log("Model '".$this->name."': ".$msg, $logLevel);
	}
	
	public function modelLoadFail(string $msg, int $logLevel = Utils::LOG_LVL_CRITICAL){
		Utils::log("Model '".$this->name."' could not be loaded: ".$msg, $logLevel);
	}
	
	/**
	 * Gets a particleID by its constant name as defined in \pocketmine\level\particle\Particle
	 * @param string $name
	 *
	 * @return int|null
	 */
	public static function getParticleIDbyName(string $name): ?int{
		if(defined("\pocketmine\level\particle\Particle::".$name)){
			return constant("\pocketmine\level\particle\Particle::".$name);
		}else{
			return null;
		}
	}
	
	/**
	 * @param mixed  $input
	 * @param string $dataIdentifier
	 *
	 * @return array|null
	 */
	public function parseParticle($input, string $dataIdentifier): ?array{
		$finalParticle = [null, null];
		if(is_int($input)){
			$finalParticle[0] = $input;
		}else{
			if(strpos($input, ":") !== false){
				$inputa = explode($input, ":");
				if(count($inputa) > 2){
					$this->modelMessage($dataIdentifier.": Has more than 2 sections, ignoring.");
				}
				if(is_int($inputa[0])){
					$finalParticle[0] = $inputa[0];
				}else{
					$finalParticle[0] = self::getParticleIDbyName($inputa[0]);
					if($finalParticle[0] == null){
						$this->modelMessage(
							"Model '" . $this->name . "': ".$dataIdentifier.": Section1: The Particle with the name '" . $inputa[0] . "' could not be found!" .
							"Please use the Particle constant names which are declared here: https://github.com/pmmp/PocketMine-MP/blob/master/src/pocketmine/level/particle/Particle.php"
						);
						return null;
					}
				}
				if(is_string($inputa[1])){
					if(strpos($inputa[1], ",") !== false){
						$inputasa = explode($input, ",");
						if(count($inputasa) > 4){
							$this->modelMessage($dataIdentifier.": Section2: Too many sections. Ignoring.");
						}
						if(count($inputasa) < 3){
							$this->modelMessage($dataIdentifier.": Section2: Must have at least 3 sections.");
							return null;
						}
						$r = $inputasa[0];
						$g = $inputasa[1];
						$b = $inputasa[2];
						$a = isset($inputasa[3]) ? $inputasa[3] : 255;
						$finalParticle[1] = (($a & 0xff) << 24) | (($r & 0xff) << 16) | (($g & 0xff) << 8) | ($b & 0xff);
					}elseif(ctype_xdigit($inputa[1])){
						$finalParticle[1] = hexdec($inputa[1]);
					}else{
						$this->modelMessage($dataIdentifier.": Unexpected Value for extraData");
						return null;
					}
				}elseif(is_int($inputa[1])){
					$finalParticle[1] = $inputa[1];
				}
			}else{
				$finalParticle[0] = self::getParticleIDbyName($input);
				if($finalParticle[0] == null){
					$this->modelMessage(
						$dataIdentifier.": The Particle with the name ".$input." could not be found! ".
						"Please use the Particle constant names which are declared here: https://github.com/pmmp/PocketMine-MP/blob/master/src/pocketmine/level/particle/Particle.php"
					);
					return null;
				}
			}
		}
		if($finalParticle[0] === null){
			$finalParticle[0] = Particle::TYPE_FLAME;
		}
		return $finalParticle;
	}
	
	
	/**
	 * Can be used to check the basic integrity of $data, provide null for $name if not applicable
	 * You may want to use this if you provide user entered data.
	 *
	 * @param array       $data    The data array to be checked
	 * @param array       $options string $optionName => [string $optionName, ?string $optionVarName, bool $isRequired]
	 *
     * @return array|string Returns array with [$optionVarName => $optionValue] on success and an string with error info
	 *                      on failure.
	 */
	public function processOptions(array $data, array $options){
		$optionVars = [];
		foreach($options as $optionName => $optionInfo){
			if(!isset($data[$optionName])){
				if($optionInfo[2]){
					return "Required key '".$optionName."' does not exist";
				}else{
					$this->modelMessage("Key '".$optionName."' does not exist, using default.", Utils::LOG_LVL_DEBUG);
					continue;
				}
			}
			switch($optionInfo[0]){
				case self::PARAMS_TYPE_STRING:
					$isValid = is_string($data[$optionName]);
					$typeStr = "string";
				break;
				case self::PARAMS_TYPE_NUMERIC:
					$isValid = is_numeric($data[$optionName]);
					$typeStr = "numeric";
				break;
				default:
					$isValid = false;
					$typeStr = "unknown";
				break;
			}
			if(!$isValid){
				if($optionInfo[2]){
					return "Required Key '".$optionName."'' exists, but is not ".$typeStr;
				}else{
					$this->modelMessage("Key '".$optionName."'' exists, but is not ".$typeStr.", ignoring.");
					continue;
				}
			}
			if($optionInfo[1] !== null){
				$optionVars[$optionInfo[1]] = $data[$optionName];
			}
		}
		return $optionVars;
	}
	
	public function isInvalid(): bool{
		return $this->isInvalid;
	}
	
	public function getName(): string{
		return $this->name;
	}
	
	public function getModelType(): string{
		return $this->modelType;
	}
	
	public function getParticle(): array{
		return $this->particleType;
	}
	
	public function getPerm(): string{
		return $this->perm;
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
	
	/** @todo */
	public function canBeUsedByPlayer(Player $player): bool{
		return true;
	}
}