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

/**
 * Saves extra data on runtime for different items
 */
namespace pocketmine\metadata;

use pocketmine\plugin\Plugin;
use pocketmine\utils\PluginException;

abstract class MetadataStore{
	/** @var \WeakMap[] */
	private array $metadataMap;

	/**
	 * Adds a metadata value to an object.
	 */
	public function setMetadata(mixed $subject, string $metadataKey, MetadataValue $newMetadataValue) : void{
		$owningPlugin = $newMetadataValue->getOwningPlugin();

		$key = $this->disambiguate($subject, $metadataKey);
		if(!isset($this->metadataMap[$key])){
			//$entry = new \WeakMap();
			$this->metadataMap[$key] = new \SplObjectStorage();//$entry;
		}else{
			$entry = $this->metadataMap[$key];
		}
		$entry[$owningPlugin] = $newMetadataValue;
	}

	/**
	 * Returns all metadata values attached to an object. If multiple
	 * have attached metadata, each will value will be included.
	 *
	 * @return MetadataValue[]
	 */
	public function getMetadata(mixed $subject, string $metadataKey) : array{
		$key = $this->disambiguate($subject, $metadataKey);
		if(isset($this->metadataMap[$key])){
			return $this->metadataMap[$key];
		}else{
			return [];
		}
	}

	/**
	 * Tests to see if a metadata attribute has been set on an object.
	 */
	public function hasMetadata(mixed $subject, string $metadataKey) : bool{
		return isset($this->metadataMap[$this->disambiguate($subject, $metadataKey)]);
	}

	/**
	 * Removes a metadata item owned by a plugin from a subject.
	 */
	public function removeMetadata(mixed $subject, string $metadataKey, Plugin $owningPlugin) : void{
		$key = $this->disambiguate($subject, $metadataKey);
		if(isset($this->metadataMap[$key])){
			unset($this->metadataMap[$key][$owningPlugin]);
			if($this->metadataMap[$key]->count() === 0){
				unset($this->metadataMap[$key]);
			}
		}
	}

	/**
	 * Invalidates all metadata in the metadata store that originates from the
	 * given plugin. Doing this will force each invalidated metadata item to
	 * be recalculated the next time it is accessed.
	 */
	public function invalidateAll(Plugin $owningPlugin) : void{
		/** @var $values MetadataValue[] */
		foreach($this->metadataMap as $values){
			if(isset($values[$owningPlugin])){
				$values[$owningPlugin]->invalidate();
			}
		}
	}

	/**
	 * Creates a unique name for the object receiving metadata by combining
	 * unique data from the subject with a metadataKey.
	 *
	 * @throws \InvalidArgumentException
	 */
	public abstract function disambiguate(Metadatable $subject, string $metadataKey) : string;
}