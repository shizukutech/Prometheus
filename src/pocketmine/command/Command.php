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
 * Command handling related classes
 */
namespace pocketmine\command;

use pocketmine\event\TextContainer;
use pocketmine\event\TimingsHandler;
use pocketmine\event\TranslationContainer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

abstract class Command{
	private string $name;

	private string $nextLabel;

	private string $label;

	/**
	 * @var string[]
	 */
	private array $aliases = [];

	/**
	 * @var string[]
	 */
	private array $activeAliases = [];

	private ?CommandMap $commandMap = null;

	protected string $description = "";

	protected string $usageMessage;

	private ?string $permission = null;

	private ?string $permissionMessage = null;

	public TimingsHandler $timings;

	/**
	 * @param string[] $aliases
	 */
	public function __construct(string $name, string $description = "", ?string $usageMessage = null, array $aliases = []){
		$this->name = $name;
		$this->nextLabel = $name;
		$this->label = $name;
		$this->description = $description;
		$this->usageMessage = $usageMessage === null ? "/" . $name : $usageMessage;
		$this->aliases = $aliases;
		$this->activeAliases = (array) $aliases;
		$this->timings = new TimingsHandler("** Command: " . $name);
	}

	/**
	 * @param string[] $args
	 */
	public abstract function execute(CommandSender $sender, string $commandLabel, array $args) : mixed;

	public function getName() : string{
		return $this->name;
	}

	public function getPermission() : ?string{
		return $this->permission;
	}

	public function setPermission(?string $permission) : void{
		$this->permission = $permission;
	}

	public function testPermission(CommandSender $target) : bool{
		if($this->testPermissionSilent($target)){
			return true;
		}

		if($this->permissionMessage === null){
			$target->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));
		}elseif($this->permissionMessage !== ""){
			$target->sendMessage(str_replace("<permission>", $this->permission, $this->permissionMessage));
		}

		return false;
	}

	public function testPermissionSilent(CommandSender $target) : bool{
		if($this->permission === null || $this->permission === ""){
			return true;
		}

		foreach(explode(";", $this->permission) as $permission){
			if($target->hasPermission($permission)){
				return true;
			}
		}

		return false;
	}

	public function getLabel() : string{
		return $this->label;
	}

	public function setLabel(string $name) : bool{
		$this->nextLabel = $name;
		if(!$this->isRegistered()){
			$this->timings = new TimingsHandler("** Command: " . $name);
			$this->label = $name;

			return true;
		}

		return false;
	}

	/**
	 * Registers the command into a Command map
	 */
	public function register(CommandMap $commandMap) : bool{
		if($this->allowChangesFrom($commandMap)){
			$this->commandMap = $commandMap;

			return true;
		}

		return false;
	}

	public function unregister(CommandMap $commandMap) : bool{
		if($this->allowChangesFrom($commandMap)){
			$this->commandMap = null;
			$this->activeAliases = $this->aliases;
			$this->label = $this->nextLabel;

			return true;
		}

		return false;
	}

	private function allowChangesFrom(CommandMap $commandMap) : bool{
		return $this->commandMap === null || $this->commandMap === $commandMap;
	}

	public function isRegistered() : bool{
		return $this->commandMap !== null;
	}

	/**
	 * @return string[]
	 */
	public function getAliases() : array{
		return $this->activeAliases;
	}

	public function getPermissionMessage() : ?string{
		return $this->permissionMessage;
	}

	public function getDescription() : string{
		return $this->description;
	}

	public function getUsage() : string{
		return $this->usageMessage;
	}

	/**
	 * @param string[] $aliases
	 */
	public function setAliases(array $aliases) : void{
		$this->aliases = $aliases;
		if(!$this->isRegistered()){
			$this->activeAliases = (array) $aliases;
		}
	}

	public function setDescription(string $description) : void{
		$this->description = $description;
	}

	public function setPermissionMessage(string $permissionMessage) : void{
		$this->permissionMessage = $permissionMessage;
	}

	public function setUsage(string $usage) : void{
		$this->usageMessage = $usage;
	}

	public static function broadcastCommandMessage(CommandSender $source, TextContainer|string $message, bool $sendToSource = true) : void{
		if($message instanceof TextContainer){
			$m = clone $message;
			$result = "[".$source->getName().": ".($source->getServer()->getLanguage()->get($m->getText()) !== $m->getText() ? "%" : "") . $m->getText() ."]";

			$users = $source->getServer()->getPluginManager()->getPermissionSubscriptions(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
			$colored = TextFormat::GRAY . TextFormat::ITALIC . $result;

			$m->setText($result);
			$result = clone $m;
			$m->setText($colored);
			$colored = clone $m;
		}else{
			$users = $source->getServer()->getPluginManager()->getPermissionSubscriptions(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
			$result = new TranslationContainer("chat.type.admin", [$source->getName(), $message]);
			$colored = new TranslationContainer(TextFormat::GRAY . TextFormat::ITALIC . "%chat.type.admin", [$source->getName(), $message]);
		}

		if($sendToSource === true && !($source instanceof ConsoleCommandSender)){
			$source->sendMessage($message);
		}

		foreach($users as $user){
			if($user instanceof CommandSender){
				if($user instanceof ConsoleCommandSender){
					$user->sendMessage($result);
				}elseif($user !== $source){
					$user->sendMessage($colored);
				}
			}
		}
	}

	public function __toString() : string{
		return $this->name;
	}
}
