<?php

namespace robske_110\PlayerParticles\Render;

use robske_110\PlayerParticles\Model\Model;

use pocketmine\level\Location;

class RenderJob{
	private static $id = 0;
	
	/** @var Location */
	private $pos;
	/** @var Model */
	private $model;
	/** @var int */
	private $uid;
	/** @var bool */
	private $active = true;
	/** @var bool */
	private $externalDeactivated = false;
	/** @var bool */
	private $isGarbage = false;
	
	public function __construct(Location $pos, Model $model){
		$this->pos = $pos;
		$this->model = $model;
		$this->uid = self::$id++;
	}

    /**
     * @param Location $newPos
     */
    public function modifyPos(Location $newPos){
		$this->pos = $newPos;
	}

    /**
     * @return Model
     */
	public function getModel(): Model{
		return $this->model;
	}

    /**
     * @return Location
     */
	public function getLocation(){
		return $this->pos;
	}
	
	/**
	 * @return int Unique ID for a RenderJob
	 */
	public function getID(): int{
		return $this->uid;
	}
	
	/**
	 * @param bool $external Never supply false, this is internal!
	 */
	public function activate(bool $external = true){
		if($this->externalDeactivated && !$external){
			return;
		}
		$this->externalDeactivated = false;
		$this->active = true;
	}
	
	/**
	 * @param bool $external Never supply false, this is internal!
	 */
	public function deactivate(bool $external = true){
		if($external){
			$this->externalDeactivated = $external;
		}
		$this->active = false;
	}

    /**
     * @return bool isActive
     */
	public function isActive(): bool{
		return $this->active;
	}
	
	public function delete(){
		$this->deactivate();
		$this->isGarbage = true;
	}
	
	/**
	 * @return bool isGarbage
	 */
	public function isGarbage(): bool{
		return $this->isGarbage;
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!