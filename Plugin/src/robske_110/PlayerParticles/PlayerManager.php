<?php

namespace robske_110\PlayerParticles;

use robske_110\PlayerParticles\Render\RenderJob;

class PlayerManager{
	/** @var PlayerParticles  */
	private $main;
	/** @var RenderJob[][] */
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
	 * @param int      $playerID
	 * @param null|int $coolDown The time in ticks until the renderJob should be activated again (default is
	 *                           hide-during-movement-cooldown)
	 *
	 * @return bool Success
	 */
	public function hideRenderJobs(int $playerID, ?int $coolDown = null): bool{
		if(!$this->hideDuringMovement || !isset($this->players[$playerID])){
			return false;
		}
		foreach($this->players[$playerID] as $renderJob){
			$renderJob->deactivate(false);
			$this->reactivateTask->addReactivateJob($renderJob, $coolDown ?? $this->hideDuringMovementCoolDown);
		}
		return true;
	}
	
	/**
	 * @param RenderJob $renderJob
	 * @param null|int  $playerID Supplying the playerID improves performance
	 *
	 * @return bool Whether the RenderJob was found and removed
	 */
	public function remRenderJob(RenderJob $renderJob, ?int $playerID = null): bool{
		$success = false;
		$renderJobID = $renderJob->getID();
		if($playerID === null){
			foreach($this->players as $playerID => $renderJobs){
				if(isset($renderJobs[$renderJobID])){
					unset($this->players[$playerID][$renderJobID]);
					$success = true;
				}
			}
		}elseif(isset($this->players[$playerID])){
			if(isset($this->players[$playerID][$renderJobID])){
				unset($this->players[$playerID][$renderJobID]);
				$success = true;
			}
		}
		if($success){
			$this->players[$playerID] = array_values($this->players[$playerID]);
		}
		return $success;
	}
	
	/**
	 * @param int       $playerID
	 * @param RenderJob $renderJob
	 *
	 * @return bool
	 */
	public function addRenderJobToPlayer(int $playerID, RenderJob $renderJob): bool{
		if(isset($this->players[$playerID])){
			$this->players[$playerID][$renderJob->getID()] = $renderJob;
			$this->main->getRenderManager()->addRenderJob($renderJob);
			return true;
		}
		return false;
	}
	
	/**
	 * @param int $playerID
	 *
	 * @return RenderJob[]|null Returns null if player doesn't exists, empty array if player has no RenderJobs
	 */
	public function getRenderJobsForPlayer(int $playerID): ?array{
		if(isset($this->players[$playerID])){
			return $this->players[$playerID];
		}
		return null;
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
	 * @return bool Whether the player existed
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