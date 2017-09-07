<?php

namespace robske_110\PlayerParticles;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

use robske_110\PlayerParticles\Render\RenderJob;

class EventListener implements Listener{
	private $main;

	public function __construct(PlayerParticles $main){
		$this->main = $main;
	}
    
	public function onMove(PlayerMoveEvent $event){
		$this->main->getPlayerManager()->hideRenderJobs($event->getPlayer()->getId());
	}
	
	public function onJoin(PlayerLoginEvent $event){
		$this->main->getPlayerManager()->addPlayer($event->getPlayer()->getId());
		/* For testing as there currently is no method to add Models to a player */
		$this->main->getPlayerManager()->addRenderJobToPlayer($event->getPlayer()->getId(), new RenderJob($event->getPlayer(), $this->main->getModel("Wing")));
	}
	
	public function onLeave(PlayerQuitEvent $event){
		$this->main->getPlayerManager()->removePlayer($event->getPlayer()->getId());
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!