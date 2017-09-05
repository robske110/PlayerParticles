<?php

namespace robske_110\PlayerParticles\Render;

use pocketmine\level\Location;

use robske_110\PlayerParticles\Model\Model;
use robske_110\PlayerParticles\Model\Model2DMap;
use robske_110\PlayerParticles\Listener;
use robske_110\PlayerParticles\PlayerParticles;
use robske_110\Utils\Utils;

use pocketmine\level\particle\GenericParticle;
use pocketmine\math\Vector3;

class Renderer{
	/** @var PlayerParticles */
	private $playerParticles;
	
	const DEG_TO_RAD = M_PI / 180;
	
	public function __construct(PlayerParticles $playerParticles){
		$this->playerParticles = $playerParticles;
	}
	
	/**
	  * Renders $model for $pos
	  *
	  * @param Location $pos The location at which particles should be spawned
	  * @param Model $model The model that should be rendered
	  *
	  * @return bool Success
	  */
	public function render(Location $pos, Model $model): bool{
		set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext){
			Utils::debug(print_r($errcontext, true));
		    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
		try{
			$px = $pos->getX();
			$pz = $pos->getZ();
			$py = $pos->getY();
			$level = $pos->getLevel();
			
			$yOffset = 2;
			switch($model->getModelType()){
				case "helix":
					$yOffset = 0;
					
					$h = 2;
					$yi = 0.02;
					$t = 0.25;
					$ti = 0.01;
					$res = 20;
					$y = $py + $yOffset;
					$rot = $model->getRuntimeData("rot");
					$rotRad = $rot * self::DEG_TO_RAD;
					$cos = cos($rotRad);
					$sin = sin($rotRad); 
					for($yaw = 0, $cy = $y; $cy < $y + $h; $yaw += (M_PI * 2) / $res, $cy += $yi, $t += $ti){
						$diffx = -sin($yaw) * $t;
						$diffz = cos($yaw) * $t;
						$rx = $diffx * $cos + $diffz * $sin;
						$rz = -$diffx * $sin + $diffz * $cos;
						$fx = $px + $rx;
						$fz = $pz + $rz;
						$particleObject = new GenericParticle(new Vector3($fx, $cy, $fz), 7);
						$level->addParticle($particleObject);
					}
				break;
				case "headcircle":
					$y = $py + $yOffset;
					$ri = 25;
					$t = 0.6;
					
					$rot = $model->getRuntimeData("rot");
					if($rot === null){
						$rot = 0;
					}
					$rot += $ri;
					if($rot > 360){
						$rot = $ri;
					}
					$model->setRuntimeData("rot", $rot);
					
					$rotRad = $rot * self::DEG_TO_RAD;
					$rx = -sin($rotRad) * $t;
					$rz = cos($rotRad) * $t;
					$fx = $px + $rx;
					$fz = $pz + $rz;
					$particleObject = new GenericParticle(new Vector3($fx, $y, $fz), 17);
					$level->addParticle($particleObject);
				break;
				case "cone":
					$t = 1;
					$s = 5;
					$d = 10;
					$v = 0.25;
					$p = 0.25;
					$y = $pos->getY();
					$dcpi = abs($t - $s) / ($d / $v);
					for($iy = $y + $d; $y <= $iy; $iy -= $v){
						$t -= $dcpi;
						$ppc = round((2 * M_PI * $t) / $p);
						for($yaw = 0; $yaw <= M_PI * 2; $yaw += (M_PI * 2) / $ppc){
							$diffx = -sin($yaw) * $t;
							$diffz = cos($yaw) * $t;
							$fx = $px + $diffx;
							$fz = $pz + $diffz;
							$particleObject = new GenericParticle(new Vector3($fx, $iy, $fz), 7);
							$level->addParticle($particleObject);
						}
					}
				break;
				case "back":
					$y = $py + $yOffset;
					$layout = $model->get2DMap();
					$yaw = $pos->getYaw(); /* 0-360 DEGREES */
					$yaw -= 45;
					$sp = $model->getSpacing();
					$yawRad = ($yaw * -1 * self::DEG_TO_RAD); /* RADIANS - Don't ask me why inverted! */
					if($model->getCenterMode() == Model2DMap::CENTER_STATIC){
						$amb = max($model->getStrlenMap()) * $sp / 2;
						$amb += $amb / M_PI; /* Please just don't ask me why! */
					}
					foreach($layout as $layer){
						$y -= $sp;
						$diffx = 0;
						$diffz = 0;
						if($model->getCenterMode() == Model2DMap::CENTER_DYNAMIC){
							$amb = strlen($layer) * $sp / 2;
							$amb += $amb / M_PI; /* Please just don't ask me why! */
						}
						for($verticalpos = strlen($layer) - 1; $verticalpos >= 0; $verticalpos--){
							$cos = cos($yawRad);
							$sin = sin($yawRad); 
							$rx = $diffx * $cos + $diffz * $sin;
							$rz = -$diffx * $sin + $diffz * $cos;
							$b = $yaw - 45;
							$bx = cos($b * self::DEG_TO_RAD) * $sp;
							$bz = sin($b * self::DEG_TO_RAD) * $sp;
							$r = $yaw + 225;
							$cx = cos($r * self::DEG_TO_RAD) * $amb;
							$cz = sin($r * self::DEG_TO_RAD) * $amb;
							$fx = $px + $rx + $bx + $cx;
							$fz = $pz + $rz + $bz + $cz;
							if($layer[$verticalpos] == "P"){
								$particleObject = new GenericParticle(new Vector3($fx, $y, $fz), 7);
								$pos->getLevel()->addParticle($particleObject);
							}
							$diffx += $sp;
							$diffz += $sp;
						}
					}
				break;
				default:
					Utils::critical("Failed to render Model '".$model->getName()."': Unknown modeltype '".$model->getModelType()."'.");
				return false;
				break;
			}
			restore_error_handler();
			return true;
		}catch(\Throwable $t){
			Utils::emergency("Failed to render Model '".$model->getName()."':");
			$this->playerParticles->getLogger()->logException($t);
			restore_error_handler();
			return false;
		}
	}
}