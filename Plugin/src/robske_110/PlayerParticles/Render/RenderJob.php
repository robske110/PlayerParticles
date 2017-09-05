<?php

namespace robske_110\PlayerParticles\Render;

use robske_110\PlayerParticles\PlayerParticles;
use robske_110\Utils\Utils;
use robske_110\PlayerParticles\Model\Model;

use pocketmine\level\Location;

class RenderJob{
	/** @var Location */
	private $pos;
	/** @var Model */
	private $model;
	/** @var bool */
	private $active = true;
	
	public function __construct(Location $pos, Model $model){
		$this->pos = $pos;
		$this->model = $model;
	}
	
	public function modifyPos(Location $newPos){
		$this->pos = $newPos;
	}
	
	public function getModel(): Model{
		return $this->model;
	}
	
	public function getLocation(){
		return $this->pos;
	}
	
	public function activate(){
		$this->active = true;
	}
	
	public function deactivate(){
		$this->active = false;
	}
	
	public function isActive(): bool{
		return $this->active;
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!