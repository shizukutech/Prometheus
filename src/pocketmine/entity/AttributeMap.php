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

class AttributeMap implements \ArrayAccess{
	/** @var Attribute[] */
	private array $attributes = [];

	public function addAttribute(Attribute $attribute) : void{
		$this->attributes[$attribute->getId()] = $attribute;
	}

	public function getAttribute(int $id) : ?Attribute{
		return $this->attributes[$id] ?? null;
	}

	/**
	 * @return Attribute[]
	 */
	public function needSend() : array{
		return array_filter($this->attributes, function (Attribute $attribute){
			return $attribute->isSyncable() && $attribute->isDesynchronized();
		});
	}

	public function offsetExists(mixed $offset) : bool{
		return isset($this->attributes[$offset]);
	}

	public function offsetGet(mixed $offset) : float{
		return $this->attributes[$offset]->getValue();
	}

	public function offsetSet(mixed $offset, mixed $value) : void{
		$this->attributes[$offset]->setValue($value);
	}

	public function offsetUnset(mixed $offset) : void{
		throw new \RuntimeException("Could not unset an attribute from an attribute map");
	}
}
