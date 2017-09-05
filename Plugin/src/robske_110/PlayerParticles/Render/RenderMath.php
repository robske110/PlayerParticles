<?php

namespace robske_110\PlayerParticles\Render;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\level\Location;

use robske_110\PlayerParticles\Model\Model;
use robske_110\PlayerParticles\Model\Model2DMap;
use robske_110\PlayerParticles\Listener;
use robske_110\Utils\Utils;
use robske_110\Utils\Translator;

use pocketmine\level\particle\GenericParticle;
use pocketmine\math\Vector3;

abstract class RenderMath{
	const DEG_TO_RAD = M_PI / 180;
	
}