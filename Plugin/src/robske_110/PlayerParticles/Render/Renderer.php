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
					$h = 2; //height
					$yi = 0.02; //height increase per particle
					$t = 0.25; //lower radius
					$ti = 0.01; //radius increase per particle
					$res = 25; //particles per circle
					$yOffset = 0;
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
					$ri = 25; //rotation increase per render (degrees)
					$t = 0.6; //diameter
					$y = $py + $yOffset;
					
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
					$t = 2; //upper diameter
					$s = 0; //lower diameter
					$d = 2.5; //height
					$v = 0.25; //vertical resolution (pixel every x blocks)
					$p = 0.25; //horizontal resolution (pixel every x blocks)
					$y = $py;
					$dcpi = abs($t - $s) / (($d / $v) + 1);
					for($iy = $y + $d; $y <= $iy; $iy -= $v){
						$t -= $dcpi;
						if($t < 1E-15 && $t > -1E-15){ /* Floating point error correction */
							$t = 0;
						}
						if($t !== 0){ /* don't attempt to render a circle with diameter 0 */
							$ppc = (2 * M_PI * $t) / $p;
						}else{
							$ppc = 1;
						}
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
					$layout = $model->get2DMap();
					$sp = $model->getSpacing();
					$mb = 0.25/*$model->getBackwardsOffset*/;
					$y = $py + $yOffset;
					$yaw = $pos->getYaw(); /* 0-360 DEGREES */
					#$yaw -= 45;
					$yawRad = ($yaw * -1 * self::DEG_TO_RAD);
					var_dump($yaw);
					if($model->getCenterMode() == Model2DMap::CENTER_STATIC){
						$amb = max($model->getStrlenMap()) * $sp / 2;
						$amb -= 0.5*$sp;
						var_dump($amb);
						//$amb += $amb / M_PI; /* Please just don't ask me why! */
					}
					
					/* moving to back */
					$b = $yaw - 90;
					$bx = cos($b * self::DEG_TO_RAD) * $mb;
					$bz = sin($b * self::DEG_TO_RAD) * $mb;
					foreach($layout as $layer){
						$y -= $sp;
						$diffx = 0/*-0.5 * strlen($layer) * $sp*/;
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
							Utils::debug("diffx: $diffx diffz: $diffz");
							Utils::debug("rx: $rx (".$diffx * $cos."+".$diffz * $sin.") rz: $rz (".-$diffx * $sin."+". $diffz * $cos.")");
							/* centering */
							$r = $yaw - 180;
							$cx = cos($r * self::DEG_TO_RAD) * $amb;
							$cz = sin($r * self::DEG_TO_RAD) * $amb;
							Utils::debug("cx: $cx cz: $cz");
							$fx = $px + $rx + $bx + $cx;
							$fz = $pz + $rz + $bz + $cz;
							if($layer[$verticalpos] == "P"){
								$particleObject = new GenericParticle(new Vector3($fx, $y, $fz), 7);
								$pos->getLevel()->addParticle($particleObject);
							}
							$diffx += $sp;
							$diffz += 0;
						}
					}
					Utils::debug("______");
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