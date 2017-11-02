<?php

namespace robske_110\Utils;

use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;

/**
  * @author robske_110
  * @version 0.9.0-php7
  */
class Translator{
	private $translationFile;
	private $fallBackFile;
	public $selectedLang;
	
	private static $langs = ['eng','deu'];
	private static $dataFolder = "";
	private static $friendlyLangNames = [
		'deu' => ['ger', 'german', 'deutsch'],
		'eng' => ['english', 'englisch']
	];
	
	public static function getLangByFriendlyName(string $friendlyName){
		$friendlyName = strtolower($friendlyName);
		foreach(self::$friendlyLangNames as $lang => $friendlyNames){
			if(in_array($friendlyName, $friendlyNames, true)){
				return $lang;
			}
		}
		return false;
	}
	
	public static function getLangFileName(string $lang){
		return "messages-".$lang.".yml";
	}  
	
	public static function getLangFilePath(string $lang){
		return self::$dataFolder.self::getLangFileName($lang);
	}
	
	public static function init(PluginBase $main){
		foreach(self::$langs as $lang){
			$main->saveResource(self::getLangFileName($lang));
		}
		self::$dataFolder = $main->getDataFolder();
	}
	
	public function __construct(string $selectedLang){
		if(!in_array($selectedLang, $langs, true)){
			Utils::critical("Invalid selectedLang: selectedLang '".$selectedLang."' not found!");
			return;
		}
		$this->translationFile = new Config(self::getLangFilePath($selectedLang), Config::YAML, []);
		$this->fallBackFile = new Config(self::getLangFilePath(self::$langs[1]), Config::YAML, []);
		$this->selectedLang = $selectedLang;
	}
	
	private function baseTranslate(string $translatedString, array $inMsgVars, string $translationString){
		Utils::debug($translatedString);
		if(is_string($translatedString)){
			$cnt = -1;
			foreach($inMsgVars as $inMsgVar){
				$cnt++;
				if(strpos($translatedString, "&&var".$cnt."&&") !== false){
					$translatedString = str_replace("&&var".$cnt."&&", $inMsgVar, $translatedString);
				}else{
					$translatedString = $translatedString." var".$cnt.$inMsgVar;
					Utils::debug("Failed to insert all variables into the translatedString. Data: "."transStr:'".$translationString."' varCnt:".$cnt." inMsgVar:'".$inMsgVar."' transEdString:'".$translatedString."'");
				}
			}
			return $translatedString;
		}
		return false;
	}
	
	public function fallbackTranslate(string $translationString, array $inMsgVars){
		$translatedString = $this->fallBackFile->getNested($translationString);
		$baseTranslateResult = $this->baseTranslate($translatedString, $inMsgVars, $translationString);
		if($baseTranslateResult !== false){
			return $baseTranslateResult;
		}else{
			Utils::warning("Failed to translate the string '".$translationString."' in the fallback lang '".self::$langs[0]."'!");
			return $translationString;
		}
	}
	
	public function translate(string $translationString, string ...$inMsgVars){
		$translatedString = $this->translationFile->getNested($translationString);
		$baseTranslateResult = $this->baseTranslate($translatedString, $inMsgVars, $translationString);
		if($baseTranslateResult !== false){
			return $baseTranslateResult;
		}else{
			Utils::debug("Failed to translate the string '".$translationString."' in the lang '".$this->selectedLang."'! Falling back to lang '".self::$langs[0]."'.");
			return $this->fallbackTranslate($translationString, $inMsgVars);		
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.