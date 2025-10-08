<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace pocketmine\block;


use pocketmine\item\Item;

use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class WaterLily extends Flowable{

	protected int $id = self::WATER_LILY;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function isSolid() : bool{
		return false;
	}

	public function getName() : string{
		return "Lily Pad";
	}

	public function getHardness() : float{
		return 0.6;
	}

	public function canPassThrough() : bool{
		return true;
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x,
			$this->y + 0.0625,
			$this->z
		);
	}


	public function place(Item $item, Block $block, Block $target, int $face, float $fx, float $fy, float $fz, ?Player $player = null) : bool{
		if($target instanceof Water){
			$up = $target->getSide(Vector3::SIDE_UP);
			if($up->getId() === Block::AIR){
				$this->getLevel()->setBlock($up, $this, true, true);
				return true;
			}
		}

		return false;
	}

	public function onUpdate(int $type) : false|int{
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if(!($this->getSide(0) instanceof Water)){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}

	public function getDrops(Item $item) : array{
		return [
			[$this->id, 0, 1]
		];
	}
}