<?php

namespace robske_110\PlayerParticles;

use robske_110\PlayerParticles\Render\RenderJob;

class PlayerManager{
	/** @var PlayerParticles  */
	private $main;
	/** @var array */
	private $players;
	/** @var bool */
	private $hideDuringMovement;
	/** @var int */
	private $hideDuringMovementCoolDown = 0;
	/** @var null|ReactivateTask */
	private $reactivateTask = null;
	
	
	public function __construct(PlayerParticles $main){
		$this->main = $main;
		$this->hideDuringMovement = $main->getConfig()->get("hide-during-movement");
		if($this->hideDuringMovement){
			$this->initReactivateTask();
		}
	}
	
	private function initReactivateTask(){
		$this->hideDuringMovementCoolDown = $this->main->getConfig()->get("hide-during-movement-cooldown");
		$this->reactivateTask = new ReactivateTask($this->main);
	}
	
	/**
	 * @param int $playerID
	 * @param int|null $ticks
	 * @param bool $force
	 *
	 * @return bool Success
	 */
	public function hideRenderJobs(int $playerID, ?int $ticks = null, bool $force = false): bool{
		if(!isset($this->players[$playerID])){
			return false;
		}
		if(!$force && !$this->hideDuringMovement){
			return false;
		}elseif($this->reactivateTask === null){
			$this->initReactivateTask();
		}
		if($ticks === null){
			$ticks = $this->hideDuringMovementCoolDown;
		}
		foreach($this->players[$playerID] as $renderJob){
			$renderJob->deactivate();
			$this->reactivateTask->addReactivateJob($renderJob, $ticks);
		}
		return true;
	}
	
	/**
	 * @param $playerID
	 * @param RenderJob $renderJob
	 *
	 * @return bool
	 */
	public function addRenderJobToPlayer($playerID, RenderJob $renderJob): bool{
		if(isset($this->players[$playerID])){
			$this->players[$playerID][] = $renderJob;
			$this->main->getRenderManager()->addRenderJob($renderJob);
			return true;
		}
		return false;
	}
	
	/**
	 * @param int $playerID
	 */
	public function addPlayer(int $playerID){
		$this->players[$playerID] = [];
	}
	
	/**
	 * @param int $playerID
	 *
	 * @return bool
	 */
	public function removePlayer(int $playerID): bool{
		if(isset($this->players[$playerID])){
			foreach($this->players[$playerID] as $renderJob){
				$renderJob->delete();
			}
			unset($this->players[$playerID]);
			return true;
		}
		return false;
	}
}