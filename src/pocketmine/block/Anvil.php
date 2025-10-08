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

use pocketmine\inventory\AnvilInventory;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\Player;

class Anvil extends Fallable{

	protected int $id = self::ANVIL;

	public function isSolid() : bool{
		return false;
	}

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function canBeActivated() : bool{
		return true;
	}

	public function getHardness() : float{
		return 5;
	}

	public function getResistance() : int{
		return 6000;
	}

	public function getName() : string{
		return "Anvil";
	}

	public function getToolType() : int{
		return Tool::TYPE_PICKAXE;
	}

	public function onActivate(Item $item, ?Player $player = null) : bool{
		if($player instanceof Player){
			if($player->isCreative()){
				return true;
			}

			$player->addWindow(new AnvilInventory($this));
		}

		return true;
	}

	public function getDrops(Item $item) : array{
		if($item->isPickaxe() >= Tool::TIER_WOODEN){
			return [
				[$this->id, 0, 1], //TODO break level
			];
		}else{
			return [];
		}
	}
}