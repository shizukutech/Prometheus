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

namespace pocketmine\entity;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item as ItemItem;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\Player;
use pocketmine\utils\UUID;

class Human extends Creature implements ProjectileSource, InventoryHolder{

	const DATA_PLAYER_FLAG_SLEEP = 1;
	const DATA_PLAYER_FLAG_DEAD = 2;

	const DATA_PLAYER_FLAGS = 16;
	const DATA_PLAYER_BED_POSITION = 17;

	protected PlayerInventory $inventory;

	protected ?UUID $uuid;
	protected ?string $rawUUID;

	public float $width = 0.6;
	public float $length = 0.6;
	public float $height = 1.8;
	public ?float $eyeHeight = 1.62;

	protected string $skinId;
	protected string $skin;

	protected int $foodTickTimer = 0;

	protected int $totalXp = 0;
	protected int $xpSeed;

	public function getSkinData() : string{
		return $this->skin;
	}

	public function getSkinId() : string{
		return $this->skinId;
	}

	public function getUniqueId() : ?UUID{
		return $this->uuid;
	}

	public function getRawUniqueId() : ?string{
		return $this->rawUUID;
	}

	public function setSkin(string $str, string $skinId) : void{
		$this->skin = $str;
		$this->skinId = $skinId;
	}

	public function getFood() : float{
		return $this->attributeMap->getAttribute(Attribute::HUNGER)->getValue();
	}

	/**
	 * WARNING: This method does not check if full and may throw an exception if out of bounds.
	 * Use {@link Human::addFood()} for this purpose
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setFood(float $new) : void{
		$attr = $this->attributeMap->getAttribute(Attribute::HUNGER);
		$old = $attr->getValue();
		$attr->setValue($new);
		// ranges: 18-20 (regen), 7-17 (none), 1-6 (no sprint), 0 (health depletion)
		foreach([17, 6, 0] as $bound){
			if(($old > $bound) !== ($new > $bound)){
				$reset = true;
			}
		}
		if(isset($reset)){
			$this->foodTickTimer = 0;
		}

	}

	public function getMaxFood() : float{
		return $this->attributeMap->getAttribute(Attribute::HUNGER)->getMaxValue();
	}

	public function addFood(float $amount) : void{
		$attr = $this->attributeMap->getAttribute(Attribute::HUNGER);
		$amount += $attr->getValue();
		$amount = max(min($amount, $attr->getMaxValue()), $attr->getMinValue());
		$this->setFood($amount);
	}

	public function getSaturation() : float{
		return $this->attributeMap->getAttribute(Attribute::SATURATION)->getValue();
	}

	/**
	 * WARNING: This method does not check if saturated and may throw an exception if out of bounds.
	 * Use {@link Human::addSaturation()} for this purpose
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setSaturation(float $saturation) : void{
		$this->attributeMap->getAttribute(Attribute::SATURATION)->setValue($saturation);
	}

	public function addSaturation(float $amount) : void{
		$attr = $this->attributeMap->getAttribute(Attribute::SATURATION);
		$attr->setValue($attr->getValue() + $amount, true);
	}

	public function getExhaustion() : float{
		return $this->attributeMap->getAttribute(Attribute::EXHAUSTION)->getValue();
	}

	/**
	 * WARNING: This method does not check if exhausted and does not consume saturation/food.
	 * Use {@link Human::exhaust()} for this purpose.
	 */
	public function setExhaustion(float $exhaustion) : void{
		$this->attributeMap->getAttribute(Attribute::EXHAUSTION)->setValue($exhaustion);
	}

	/**
	 * Increases a human's exhaustion level.
	 *
	 * @return float the amount of exhaustion level increased
	 */
	public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float{
		$this->server->getPluginManager()->callEvent($ev = new PlayerExhaustEvent($this, $amount, $cause));
		if($ev->isCancelled()){
			return 0.0;
		}

		$exhaustion = $this->getExhaustion();
		$exhaustion += $ev->getAmount();

		while($exhaustion >= 4.0){
			$exhaustion -= 4.0;

			$saturation = $this->getSaturation();
			if($saturation > 0){
				$saturation = max(0, $saturation - 1.0);
				$this->setSaturation($saturation);
			}else{
				$food = $this->getFood();
				if($food > 0){
					$food--;
					$this->setFood($food);
				}
			}
		}
		$this->setExhaustion($exhaustion);

		return $ev->getAmount();
	}

	public function getXpLevel() : int{
		return (int) $this->attributeMap->getAttribute(Attribute::EXPERIENCE_LEVEL)->getValue();
	}

	public function setXpLevel(int $level) : void{
		$this->attributeMap->getAttribute(Attribute::EXPERIENCE_LEVEL)->setValue($level);
	}

	public function getXpProgress() : float{
		return $this->attributeMap->getAttribute(Attribute::EXPERIENCE)->getValue();
	}

	public function setXpProgress(float $progress) : void{
		$this->attributeMap->getAttribute(Attribute::EXPERIENCE)->setValue($progress);
	}

	public function getTotalXp() : float{
		return $this->totalXp;
	}

	public function getRemainderXp() : int{
		return $this->getTotalXp() - self::getTotalXpForLevel($this->getXpLevel());
	}

	public function recalculateXpProgress() : float{
		$this->setXpProgress($progress = $this->getRemainderXp() / self::getTotalXpForLevel($this->getXpLevel()));
		return $progress;
	}

	public static function getTotalXpForLevel(int $level) : int{
		if($level <= 16){
			return $level ** 2 + $level * 6;
		}elseif($level < 32){
			return $level ** 2 * 2.5 - 40.5 * $level + 360;
		}
		return $level ** 2 * 4.5 - 162.5 * $level + 2220;
	}

	public function getInventory() : PlayerInventory{
		return $this->inventory;
	}

	protected function initEntity() : void{

		$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false);
		$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0], false);

		$this->inventory = new PlayerInventory($this);
		if($this instanceof Player){
			$this->addWindow($this->inventory, 0);
		}else{
			if(isset($this->namedtag->NameTag)){
				$this->setNameTag($this->namedtag["NameTag"]);
			}

			if(isset($this->namedtag->Skin) && $this->namedtag->Skin instanceof CompoundTag){
				$this->setSkin($this->namedtag->Skin["Data"], $this->namedtag->Skin["Name"]);
			}

			$this->uuid = UUID::fromData($this->getId(), $this->getSkinData(), $this->getNameTag());
		}

		if(isset($this->namedtag->Inventory) && $this->namedtag->Inventory instanceof ListTag){
			foreach($this->namedtag->Inventory as $item){
				if($item["Slot"] >= 0 && $item["Slot"] < 9){ //Hotbar
					$this->inventory->setHotbarSlotIndex($item["Slot"], isset($item["TrueSlot"]) ? $item["TrueSlot"] : -1);
				}elseif($item["Slot"] >= 100 && $item["Slot"] < 104){ //Armor
					$this->inventory->setItem($this->inventory->getSize() + $item["Slot"] - 100, NBT::getItemHelper($item));
				}else{
					$this->inventory->setItem($item["Slot"] - 9, NBT::getItemHelper($item));
				}
			}
		}

		parent::initEntity();

		if(!isset($this->namedtag->foodLevel) || !($this->namedtag->foodLevel instanceof IntTag)){
			$this->namedtag->foodLevel = new IntTag("foodLevel", $this->getFood());
		}else{
			$this->setFood($this->namedtag["foodLevel"]);
		}

		if(!isset($this->namedtag->foodExhaustionLevel) || !($this->namedtag->foodExhaustionLevel instanceof IntTag)){
			$this->namedtag->foodExhaustionLevel = new FloatTag("foodExhaustionLevel", $this->getExhaustion());
		}else{
			$this->setExhaustion($this->namedtag["foodExhaustionLevel"]);
		}

		if(!isset($this->namedtag->foodSaturationLevel) || !($this->namedtag->foodSaturationLevel instanceof IntTag)){
			$this->namedtag->foodSaturationLevel = new FloatTag("foodSaturationLevel", $this->getSaturation());
		}else{
			$this->setSaturation($this->namedtag["foodSaturationLevel"]);
		}

		if(!isset($this->namedtag->foodTickTimer) || !($this->namedtag->foodTickTimer instanceof IntTag)){
			$this->namedtag->foodTickTimer = new IntTag("foodTickTimer", $this->foodTickTimer);
		}else{
			$this->foodTickTimer = $this->namedtag["foodTickTimer"];
		}

		if(!isset($this->namedtag->XpLevel) || !($this->namedtag->XpLevel instanceof IntTag)){
			$this->namedtag->XpLevel = new IntTag("XpLevel", $this->getXpLevel());
		}else{
			$this->setXpLevel($this->namedtag["XpLevel"]);
		}

		if(!isset($this->namedtag->XpP) || !($this->namedtag->XpP instanceof FloatTag)){
			$this->namedtag->XpP = new FloatTag("XpP", $this->getXpProgress());
		}

		if(!isset($this->namedtag->XpTotal) || !($this->namedtag->XpTotal instanceof IntTag)){
			$this->namedtag->XpTotal = new IntTag("XpTotal", $this->totalXp);
		}else{
			$this->totalXp = $this->namedtag["XpTotal"];
		}

		if(!isset($this->namedtag->XpSeed) || !($this->namedtag->XpSeed instanceof IntTag)){
			$this->namedtag->XpSeed = new IntTag("XpSeed", $this->xpSeed ?? ($this->xpSeed = mt_rand(PHP_INT_MIN, PHP_INT_MAX)));
		}else{
			$this->xpSeed = $this->namedtag["XpSeed"];
		}
	}

	protected function addAttributes() : void{
		parent::addAttributes();

		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::SATURATION));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXHAUSTION));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::HUNGER));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXPERIENCE_LEVEL));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXPERIENCE));
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		$food = $this->getFood();
		$health = $this->getHealth();
		if($food >= 18){
			$this->foodTickTimer++;
			if($this->foodTickTimer >= 80 && $health < $this->getMaxHealth()){
				$this->heal(1, new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_SATURATION));
				$this->exhaust(3.0, PlayerExhaustEvent::CAUSE_HEALTH_REGEN);
				$this->foodTickTimer = 0;

			}
		}elseif($food === 0){
			$this->foodTickTimer++;
			if($this->foodTickTimer >= 80){
				$diff = $this->server->getDifficulty();
				$can = false;
				if($diff === 1){
					$can = $health > 10;
				}elseif($diff === 2){
					$can = $health > 1;
				}elseif($diff === 3){
					$can = true;
				}
				if($can){
					$this->attack(1, new EntityDamageEvent($this, EntityDamageEvent::CAUSE_STARVATION, 1));
				}
			}
		}
		if($food <= 6){
			if($this->isSprinting()){
				$this->setSprinting(false);
			}
		}

		return $hasUpdate;
	}

	public function getName() : string{
		return $this->getNameTag();
	}

	public function getDrops() : array{
		$drops = [];
		foreach($this->inventory->getContents() as $item){
			$drops[] = $item;
		}

		return $drops;
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->namedtag->Inventory = new ListTag("Inventory", []);
		$this->namedtag->Inventory->setTagType(NBT::TAG_Compound);
		for($slot = 0; $slot < 9; ++$slot){
			$hotbarSlot = $this->inventory->getHotbarSlotIndex($slot);
			if($hotbarSlot !== -1){
				$item = $this->inventory->getItem($hotbarSlot);
				if($item->getId() !== 0 && $item->getCount() > 0){
					$tag = NBT::putItemHelper($item, $slot);
					$tag->TrueSlot = new ByteTag("TrueSlot", $hotbarSlot);
					$this->namedtag->Inventory[$slot] = $tag;

					continue;
				}
			}

			$this->namedtag->Inventory[$slot] = new CompoundTag("", [
				new ByteTag("Count", 0),
				new ShortTag("Damage", 0),
				new ByteTag("Slot", $slot),
				new ByteTag("TrueSlot", -1),
				new ShortTag("id", 0),
			]);
		}

		//Normal inventory
		$slotCount = Player::SURVIVAL_SLOTS + 9;
		//$slotCount = (($this instanceof Player and ($this->gamemode & 0x01) === 1) ? Player::CREATIVE_SLOTS : Player::SURVIVAL_SLOTS) + 9;
		for($slot = 9; $slot < $slotCount; ++$slot){
			$item = $this->inventory->getItem($slot - 9);
			$this->namedtag->Inventory[$slot] = NBT::putItemHelper($item, $slot);
		}

		//Armor
		for($slot = 100; $slot < 104; ++$slot){
			$item = $this->inventory->getItem($this->inventory->getSize() + $slot - 100);
			if($item instanceof ItemItem && $item->getId() !== ItemItem::AIR){
				$this->namedtag->Inventory[$slot] = NBT::putItemHelper($item, $slot);
			}
		}

		if(strlen($this->getSkinData()) > 0){
			$this->namedtag->Skin = new CompoundTag("Skin", [
				"Data" => new StringTag("Data", $this->getSkinData()),
				"Name" => new StringTag("Name", $this->getSkinId())
			]);
		}
	}

	public function spawnTo(Player $player) : void{
		if($player !== $this && !isset($this->hasSpawned[$player->getLoaderId()])){
			$this->hasSpawned[$player->getLoaderId()] = $player;

			if(strlen($this->skin) < 64 * 32 * 4){
				throw new \InvalidStateException((new \ReflectionClass($this))->getShortName() . " must have a valid skin set");
			}

			if(!($this instanceof Player)){
				$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getName(), $this->skinId, $this->skin, [$player]);
			}

			$pk = new AddPlayerPacket();
			$pk->uuid = $this->getUniqueId();
			$pk->username = $this->getName();
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->item = $this->getInventory()->getItemInHand();
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);

			$this->inventory->sendArmorContents($player);

			if(!($this instanceof Player)){
				$this->server->removePlayerListData($this->getUniqueId(), [$player]);
			}
		}
	}

	public function despawnFrom(Player $player) : void{
		if(isset($this->hasSpawned[$player->getLoaderId()])){

			$pk = new RemoveEntityPacket();
			$pk->eid = $this->getId();

			$player->dataPacket($pk);
			unset($this->hasSpawned[$player->getLoaderId()]);
		}
	}

	public function close() : void{
		if(!$this->closed){
			if(!($this instanceof Player) || $this->loggedIn){
				foreach($this->inventory->getViewers() as $viewer){
					$viewer->removeWindow($this->inventory);
				}
			}
			parent::close();
		}
	}
}
