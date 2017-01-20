<?php

namespace robske_110\PlayerParticles;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listener;

class EventListener implements Listener{
	private $main;

	public function __construct(PlayerParticles $main){
		$this->main = $main;
	}
    
	public function onMove(PlayerMoveEvent $event){
		$this->main->getRenderJobsFor($event->getPlayer){
			
		}
	}
	
	public function onJoin(PlayerLoginEvent $event){
		
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!