<?php

namespace robske_110\PlayerParticles\Model;

use robske_110\PlayerParticles\Model\Model;
use robske_110\PlayerParticles\Model\Model2DMap;

abstract class ModelManager{
	
	/** @var array */
	private static $modelList = [];

	/** @var array  */
	private static $defaults = [
		"back" => Model2DMap::class,
		"headcirle" => Model::class,
        "helix" => Model::class,
		"cone" => Model::class,
	];
	
	public static function init(){
		self::registerDefaults();
	}

	public static function registerDefaults(){
		foreach(self::$defaults as $modelType => $className){
			self::registerModelType($modelType, $className);
		}
	}
	
	/**
     * @param string $modelType
     * @return null|string
     */
	public static function getClassNameForModelType(string $modelType): ?string{
		if(isset(self::$modelList[$modelType])){
			return self::$modelList[$modelType];
		}else{
			return null;
		}
	}
	
	/**
	 * @return array $modelList [$modelType => $className]
	 */
	public static function getModelList(): array{
		return self::$modelList;
	}
	
	/**
	 * @param string $modelType The modelType of your Model (should match $model->getId())
	 * @param string $className The class string of your Model (Can be obtained by ClassName::class)
	 */
	public static function registerModelType(string $modelType, string $className){
		self::$modelList[$modelType] = $className;
	}

    /**
     * @param string $modelType
     *
     * @return null|string $className The className of the unregistered modelType.
     */
	public function unregisterModelType(string $modelType): ?string{
		$className = null;
		if(isset(self::$modelList[$modelType])){
			$className = self::$modelList[$modelType];
			unset(self::$modelList[$modelType]);
		}
		return $className;
	}
	
	/**
     * @param string $className
     *
	 * @return null|string $modelType The modelType of the unregistered class.
	 */
	public function unregisterModelTypeByClassName(string $className): ?string{
		foreach(self::$modelList as $modelType => $modelClassName){
			if($className == $modelClassName){
				unset(self::$modelList[$modelType]);
				return $modelType;
			}
		}
		return null;
	}
}