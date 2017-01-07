<?php

namespace robske_110\Utils;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

/**
  * @author robske_110
  * @version 0.6.0-php7
  */
abstract class Utils{
	private static $logger;
	private static $debugEnabled;
	private static $debugFile;
	private static $prefix;
    
	const LOG_LVL_INFO = 0;
	const LOG_LVL_NOTICE = 1;
	const LOG_LVL_WARNING = 2;
	const LOG_LVL_CRITICAL = 3;
	const LOG_LVL_EMERGENCY = 4;
	const LOG_LVL_DEBUG = 5;
    
	const DEBUG_LVL_IMPORTED = 0;
	const DEBUG_LVL_NORMAL = 1;
	const DEBUG_LVL_PRIOR = 2;
    
	public static function init(PluginBase $main, bool $debugEnabled = false, string $prefix = ""){
		self::$logger = $main->getLogger();
		self::$debugEnabled = $debugEnabled;
		if($prefix !== ""){
			$prefix .= " ";
		}
		self::$prefix = $prefix;
		if($debugEnabled){
			$filename = $main->getDataFolder()."Debug".date("d:m:Y_H-i-s", time()).".txt";
			self::$debugFile = fopen($filename,'w+');
			if(!self::$debugFile){
				self::$debugEnabled = false;
				self::warning("Failed to create/open '".$filename."' for writing! Writing debug to file is disabled!");
			}
		}
	}
	public static function close(){
		if(self::$debugEnabled){
			fclose(self::$debugFile);
		}
	}
    
	public static function sendMsgToSender(CommandSender $sender, string $msg){
		if($sender instanceof Player){
			$sender->getPlayer()->sendMessage(self::$prefix.$message);
			return;
		}
		if($sender instanceof ConsoleCommandSender){
			self::log($msg);
			return;
		}
		$sender->sendMessage($msg);
	}
	private static function getTypeAsNameOfSender(CommandSender $sender){
		if($sender instanceof Player){
			return $sender->getPlayer()->getName();
		}
		if($sender instanceof ConsoleCommandSender){
			return "CONSOLE";
		}
		return "UNKNOWN";
	}
	
	public static function log(string $msg, int $logLvl = self::LOG_LVL_INFO){
		switch($logLvl){
			case self::LOG_LVL_INFO: self::$logger->info($msg); break;
			case self::LOG_LVL_NOTICE: self::$logger->notice($msg); break;
			case self::LOG_LVL_WARNING: self::$logger->warning($msg); break;
			case self::LOG_LVL_CRITICAL: self::$logger->critical($msg); break;
			case self::LOG_LVL_EMERGENCY: self::$logger->emergency($msg); break;
			case self::LOG_LVL_DEBUG: self::$logger->debug($msg); break;
		}
	}
    
	public static function notice(string $msg){
		self::log($msg, self::LOG_LVL_NOTICE);
		self::debug($msg, self::DEBUG_LVL_IMPORTED);
	}
	
	public static function warning(string $msg){
		self::log($msg, self::LOG_LVL_WARNING);
		self::debug($msg, self::DEBUG_LVL_IMPORTED);
	}
	public static function critical(string $msg){
		self::log($msg, self::LOG_LVL_CRITICAL);
		self::debug($msg, self::DEBUG_LVL_IMPORTED);
	}
	public static function emergency(string $msg){
		self::log($msg, self::LOG_LVL_EMERGENCY);
		self::debug($msg, self::DEBUG_LVL_IMPORTED);
	}
        
	public static function debug(string $msg, int $debugLvl = self::DEBUG_LVL_NORMAL){
		if($debugLvl !== self::DEBUG_LVL_IMPORTED){
			self::log($msg, self::LOG_LVL_DEBUG);
		}
		if(self::$debugEnabled){
			switch($debugLvl){
				case self::DEBUG_LVL_IMPORTED: $msg = "[IMPORTED] ".$msg."<"; break; //Imported debug msgs are imported from logger notice, critical, warning and emergency msgs.
				case self::DEBUG_LVL_NORMAL: $msg = "[NORMAL] ".$msg."<"; break;
				case self::DEBUG_LVL_PRIOR: $msg = "[PRIOR] !".$msg."!<"; break;
			}
			$msg .= "\n";
			fwrite(self::$debugFile, $msg);
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.