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
 * Permission related classes
 */
namespace pocketmine\permission;

use pocketmine\Server;

/**
 * Represents a permission
 */
class Permission{
	const DEFAULT_OP = "op";
	const DEFAULT_NOT_OP = "notop";
	const DEFAULT_TRUE = "true";
	const DEFAULT_FALSE = "false";

	public static string $DEFAULT_PERMISSION = self::DEFAULT_OP;

	public static function getByName(bool|string $value) : string{
		if(is_bool($value)){
			if($value === true){
				return "true";
			}else{
				return "false";
			}
		}
		switch(strtolower($value)){
			case "op":
			case "isop":
			case "operator":
			case "isoperator":
			case "admin":
			case "isadmin":
				return self::DEFAULT_OP;

			case "!op":
			case "notop":
			case "!operator":
			case "notoperator":
			case "!admin":
			case "notadmin":
				return self::DEFAULT_NOT_OP;

			case "true":
				return self::DEFAULT_TRUE;

			default:
				return self::DEFAULT_FALSE;
		}
	}

	private string $name;

	private string $description;

	/**
	 * @var string[]
	 */
	private array $children = [];

	private string $defaultValue;

	/**
	 * Creates a new Permission object to be attached to Permissible objects
	 *
	 * @param Permission[] $children
	 */
	public function __construct(string $name, ?string $description = null, ?string $defaultValue = null, array $children = []){
		$this->name = $name;
		$this->description = $description !== null ? $description : "";
		$this->defaultValue = $defaultValue !== null ? $defaultValue : self::$DEFAULT_PERMISSION;
		$this->children = $children;

		$this->recalculatePermissibles();
	}

	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string[]
	 */
	public function &getChildren() : array{
		return $this->children;
	}

	public function getDefault() : string{
		return $this->defaultValue;
	}

	public function setDefault(string $value) : void{
		if($value !== $this->defaultValue){
			$this->defaultValue = $value;
			$this->recalculatePermissibles();
		}
	}

	public function getDescription() : string{
		return $this->description;
	}

	public function setDescription(string $value) : void{
		$this->description = $value;
	}

	/**
	 * @return Permissible[]
	 */
	public function getPermissibles() : array{
		return Server::getInstance()->getPluginManager()->getPermissionSubscriptions($this->name);
	}

	public function recalculatePermissibles() : void{
		$perms = $this->getPermissibles();

		Server::getInstance()->getPluginManager()->recalculatePermissionDefaults($this);

		foreach($perms as $p){
			$p->recalculatePermissions();
		}
	}


	/**
	 * @return Permission|null Permission if $name is a string, void if it's a Permission
	 */
	public function addParent(Permission|string $name, bool $value) : ?Permission{
		if($name instanceof Permission){
			$name->getChildren()[$this->getName()] = $value;
			$name->recalculatePermissibles();
			return null;
		}else{
			$perm = Server::getInstance()->getPluginManager()->getPermission($name);
			if($perm === null){
				$perm = new Permission($name);
				Server::getInstance()->getPluginManager()->addPermission($perm);
			}

			$this->addParent($perm, $value);

			return $perm;
		}
	}

	/**
	 * @return Permission[]
	 */
	public static function loadPermissions(array $data, string $default = self::DEFAULT_OP) : array{
		$result = [];
		foreach($data as $key => $entry){
			$result[] = self::loadPermission($key, $entry, $default, $result);
		}

		return $result;
	}

	/**
	 * @throws \Exception
	 */
	public static function loadPermission(string $name, array $data, string $default = self::DEFAULT_OP, array &$output = []) : Permission{
		$desc = null;
		$children = [];
		if(isset($data["default"])){
			$value = Permission::getByName($data["default"]);
			if($value !== null){
				$default = $value;
			}else{
				throw new \InvalidStateException("'default' key contained unknown value");
			}
		}

		if(isset($data["children"])){
			if(is_array($data["children"])){
				foreach($data["children"] as $k => $v){
					if(is_array($v)){
						if(($perm = self::loadPermission($k, $v, $default, $output)) !== null){
							$output[] = $perm;
						}
					}
					$children[$k] = true;
				}
			}else{
				throw new \InvalidStateException("'children' key is of wrong type");
			}
		}

		if(isset($data["description"])){
			$desc = $data["description"];
		}

		return new Permission($name, $desc, $default, $children);

	}


}