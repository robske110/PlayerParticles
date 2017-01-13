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
		$model = $this->main->getModel('Wing');
		$pos = $event->getPlayer();
		#$this->main->render($pos, $model);
	}
	
	public function onJoin(PlayerLoginEvent $event){
		
	}
    
	private function parsePromptMsg($msg, $data, $sender){
		$doEnd = true;
		if($msg == "abort"){
			$this->sendMsgToSender($sender, TF::RED."Aborted the warnpardon prompt"); //TODO::Translate
		}elseif($msg == "last"){
			$remResult = $this->removeLastWarn($playerName);
			var_dump($remResult);
			if($remResult["warnsys"] && $remResult["clientBan"] && $remResult["ipBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."The last warn from '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been removed! He/she has been unbanned. A server restart may be necassary."); //TODO::Translate TODO::FixServerRestartNeed
			}elseif($remResult["warnsys"] && $remResult["clientBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."The last warn from '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been removed! He/she has been unbanned."); //TODO::Translate
			}elseif($remResult["warnsys"]){
				$this->sendMsgToSender($sender, TF::GREEN."The last warn from '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been removed!"); //TODO::Translate
			}else{
				$this->sendMsgToSender($sender, TF::RED."The player '".TF::DARK_GRAY.$playerName.TF::RED."' has no warnings!"); //TODO::Translate
			}
		}elseif($msg == "all"){
			$wipeResult = $this->wipePlayer($playerName);
			var_dump($wipeResult);
			if($wipeResult["warnsys"] && $wipeResult["clientBan"] && $wipeResult["ipBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."All warns from '".TF::DARK_GRAY.$playerName.TF::GREEN."' have been removed! A server restart may be necassary."); //TODO::Translate TODO::FixServerRestartNeed
			}elseif($wipeResult["warnsys"]){
				$this->sendMsgToSender($sender, TF::GREEN."All warns from '".TF::DARK_GRAY.$playerName.TF::GREEN."' have been removed!"); //TODO::Translate
			}else{
				$this->sendMsgToSender($sender, TF::RED."The player '".TF::DARK_GRAY.$playerName.TF::RED."' has no warnings!"); //TODO::Translate
			}
		}else{
			$this->sendMsgToSender($sender, TF::GREEN."You are currently in the warnpardon prompt (Player: '".TF::DARK_GRAY.$playerName.TF::GREEN."')"); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."If you want to abort this simply type 'abort'"); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."Type 'all' to remove all warns."); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."Type 'last' to remove the last warn."); //TODO::Translate
			$doEnd = false;
		}
		return $doEnd;
	}
	
	public function onChat(PlayerChatEvent $event){
		if(isset($this->tempWPusers[$event->getPlayer()->getName()])){
			$msg = strtolower($event->getMessage());
			$sender = $event->getPlayer();
			$data = $this->promptUsers[$event->getPlayer()->getName()];
			$event->setCancelled(true);
			if($this->parseWPpromptMsg($msg, $data, $sender)){
				unset($this->promptUsers[$event->getPlayer()->getName()]);
			}
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!