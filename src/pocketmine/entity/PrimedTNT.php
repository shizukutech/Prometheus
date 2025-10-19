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

use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class PrimedTNT extends Entity implements Explosive{
	const NETWORK_ID = 65;

	public float $width = 0.98;
	public float $length = 0.98;
	public float $height = 0.98;

	protected float $gravity = 0.04;
	protected float $drag = 0.02;

	protected int $fuse;

	public bool $canCollide = false;


	public function attack(float $damage, EntityDamageEvent $source) : void{
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			parent::attack($damage, $source);
		}
	}

	protected function initEntity() : void{
		parent::initEntity();

		if(isset($this->namedtag->Fuse)){
			$this->fuse = $this->namedtag["Fuse"];
		}else{
			$this->fuse = 80;
		}
	}


	public function canCollideWith(Entity $entity) : bool{
		return false;
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->namedtag->Fuse = new ByteTag("Fuse", $this->fuse);
	}

	public function onUpdate(int $currentTick) : bool{

		if($this->closed){
			return false;
		}

		$this->timings->startTiming();

		$tickDiff = $currentTick - $this->lastUpdate;
		if($tickDiff <= 0 && !$this->justCreated){
			return true;
		}
		$this->lastUpdate = $currentTick;

		$hasUpdate = $this->entityBaseTick($tickDiff);

		if($this->isAlive()){

			$this->motionY -= $this->gravity;

			$this->move($this->motionX, $this->motionY, $this->motionZ);

			$friction = 1 - $this->drag;

			$this->motionX *= $friction;
			$this->motionY *= $friction;
			$this->motionZ *= $friction;

			$this->updateMovement();

			if($this->onGround){
				$this->motionY *= -0.5;
				$this->motionX *= 0.7;
				$this->motionZ *= 0.7;
			}

			$this->fuse -= $tickDiff;

			if($this->fuse <= 0){
				$this->kill();
				$this->explode();
			}

		}


		return $hasUpdate || $this->fuse >= 0 || abs($this->motionX) > 0.00001 || abs($this->motionY) > 0.00001 || abs($this->motionZ) > 0.00001;
	}

	public function explode() : void{
		$this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 4));

		if(!$ev->isCancelled()){
			$explosion = new Explosion($this, $ev->getForce(), $this);
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
	}

	public function spawnTo(Player $player) : void{
		$pk = new AddEntityPacket();
		$pk->type = PrimedTNT::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}
}