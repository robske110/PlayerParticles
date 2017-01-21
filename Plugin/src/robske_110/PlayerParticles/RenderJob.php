<?php

namespace robske_110\PlayerParticles\Model;

use robske_110\PlayerParticles\PlayerParticles;
use robske_110\Utils\Utils;
use pocketmine\level\Location;

class RenderJob{
	private $pos;
	private $active = true;
	
	public function __construct(Location $pos){
		$this->pos = $pos;
		
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