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
use pocketmine\item\Tool;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Furnace;
use pocketmine\tile\Tile;

class BurningFurnace extends Solid{

	protected int $id = self::BURNING_FURNACE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Burning Furnace";
	}

	public function canBeActivated() : bool{
		return true;
	}

	public function getHardness() : float{
		return 3.5;
	}

	public function getToolType() : int{
		return Tool::TYPE_PICKAXE;
	}

	public function getLightLevel() : int{
		return 13;
	}

	public function place(Item $item, Block $block, Block $target, int $face, float $fx, float $fy, float $fz, ?Player $player = null) : bool{
		$faces = [
			0 => 4,
			1 => 2,
			2 => 5,
			3 => 3,
		];
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];
		$this->getLevel()->setBlock($block, $this, true, true);
		$nbt = new CompoundTag("", [
			new ListTag("Items", []),
			new StringTag("id", Tile::FURNACE),
			new IntTag("x", $this->x),
			new IntTag("y", $this->y),
			new IntTag("z", $this->z)
		]);
		$nbt->Items->setTagType(NBT::TAG_Compound);

		if($item->hasCustomName()){
			$nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
		}

		if($item->hasCustomBlockData()){
			foreach($item->getCustomBlockData() as $key => $v){
				$nbt->{$key} = $v;
			}
		}

		Tile::createTile("Furnace", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);

		return true;
	}

	public function onBreak(Item $item) : bool{
		$this->getLevel()->setBlock($this, new Air(), true, true);

		return true;
	}

	public function onActivate(Item $item, ?Player $player = null) : bool{
		if($player instanceof Player){
			$t = $this->getLevel()->getTile($this);
			$furnace = false;
			if($t instanceof Furnace){
				$furnace = $t;
			}else{
				$nbt = new CompoundTag("", [
					new ListTag("Items", []),
					new StringTag("id", Tile::FURNACE),
					new IntTag("x", $this->x),
					new IntTag("y", $this->y),
					new IntTag("z", $this->z)
				]);
				$nbt->Items->setTagType(NBT::TAG_Compound);
				$furnace = Tile::createTile("Furnace", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
			}

			if(isset($furnace->namedtag->Lock) && $furnace->namedtag->Lock instanceof StringTag){
				if($furnace->namedtag->Lock->getValue() !== $item->getCustomName()){
					return true;
				}
			}

			if($player->isCreative()){
				return true;
			}

			$player->addWindow($furnace->getInventory());
		}

		return true;
	}

	public function getDrops(Item $item) : array{
		$drops = [];
		if($item->isPickaxe() >= Tool::TIER_WOODEN){
			$drops[] = [Item::FURNACE, 0, 1];
		}

		return $drops;
	}
}