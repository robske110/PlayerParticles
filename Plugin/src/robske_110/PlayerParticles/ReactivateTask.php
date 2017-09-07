<?php

namespace robske_110\PlayerParticles;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use robske_110\Utils\Utils;
use robske_110\PlayerParticles\Render\RenderJob;

class ReactivateTask extends PluginTask{
	/** @var PlayerParticles */
	private $main;
	/** @var array */
	private $reactivateJobs = [];
	/** @var int */
	private $currTick;
	/** @var int */
	private $minTickDelay = PHP_INT_MAX;
	
	public function __construct(Plugin $main){
		$this->main = $main;
		parent::__construct($main);
	}
	
	/**
	 * @param RenderJob $renderJob
	 * @param int $tickDelay
	 */
	public function addReactivateJob(RenderJob $renderJob, int $tickDelay){
		$this->reactivateJobs[] = [$renderJob, $this->currTick + $tickDelay];
		$this->minReactivateTaskInterval($tickDelay);
	}
	
	/**
	 * @param int $tickDelay
	 */
	private function minReactivateTaskInterval(int $tickDelay){
		if($tickDelay < $this->minTickDelay){
			$this->minTickDelay = $tickDelay;
			$this->reschedule();
		}
	}
	
	private function recalculateReactivateTaskInterval(){
		$oldMinTickDelay = $this->minTickDelay;
		$this->minTickDelay = PHP_INT_MAX;
		foreach($this->reactivateJobs as $data){
			if($data[1] < $this->minTickDelay){
				$this->minTickDelay = $data[1];
			}
		}
		if($this->minTickDelay === PHP_INT_MAX){
			$this->minTickDelay = $oldMinTickDelay;
		}
		if($oldMinTickDelay !== $this->minTickDelay){
			$this->reschedule();
		}
	}
	
	private function reschedule(){
		if(($tid = $this->getTaskId()) !== null){
			$this->main->getServer()->getScheduler()->cancelTask($tid);
		}
		$this->main->getServer()->getScheduler()->scheduleRepeatingTask($this, $this->minTickDelay);
	}
	
	public function onRun(int $currentTick){
		$this->currTick = $currentTick;
		$needRecalculate = false;
		foreach($this->reactivateJobs as $index => $data){
			if($data[1] <= $currentTick){
				$data[0]->activate();
				unset($this->reactivateJobs[$index]);
				$needRecalculate = true;
			}
		}
		if($needRecalculate){
			$this->recalculateReactivateTaskInterval();
		}
	}
}