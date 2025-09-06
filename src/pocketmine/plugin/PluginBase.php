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

namespace pocketmine\plugin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Server;
use pocketmine\utils\Config;

abstract class PluginBase implements Plugin
{

    /** @var PluginLoader */
    private PluginLoader $loader;

    /** @var \pocketmine\Server */
    private Server $server;

    /** @var bool */
    private bool $isEnabled = false;

    /** @var bool */
    private bool $initialized = false;

    /** @var PluginDescription */
    private PluginDescription $description;

    /** @var string */
    private string $dataFolder;
    private Config $config;
    /** @var string */
    private string $configFile;
    private string $file;

    /** @var PluginLogger */
    private PluginLogger $logger;

    /**
     * Called when the plugin is loaded, before calling onEnable()
     */
    public function onLoad(): void
    {

    }

    public function onEnable(): void
    {

    }

    public function onDisable(): void
    {

    }

    /**
     * @return bool
     */
    final public function isEnabled(): bool
    {
        return $this->isEnabled === true;
    }

    /**
     * @param bool $boolean
     */
    final public function setEnabled(bool $boolean = true): void
    {
        if ($this->isEnabled !== $boolean) {
            $this->isEnabled = $boolean;
            if ($this->isEnabled === true) {
                $this->onEnable();
            } else {
                $this->onDisable();
            }
        }
    }

    /**
     * @return bool
     */
    final public function isDisabled(): bool
    {
        return $this->isEnabled === false;
    }

    final public function getDataFolder(): string
    {
        return $this->dataFolder;
    }

    final public function getDescription(): PluginDescription
    {
        return $this->description;
    }

    final public function init(
        PluginLoader $loader,
        Server $server,
        PluginDescription $description,
        string $dataFolder,
        string $file
    ): void {
        if ($this->initialized === false) {
            $this->initialized = true;
            $this->loader = $loader;
            $this->server = $server;
            $this->description = $description;
            $this->dataFolder = rtrim($dataFolder, "\\/") . "/";
            $this->file = rtrim($file, "\\/") . "/";
            $this->configFile = $this->dataFolder . "config.yml";
            $this->logger = new PluginLogger($this);
        }
    }

    /**
     * @return PluginLogger
     */
    public function getLogger(): PluginLogger
    {
        return $this->logger;
    }

    /**
     * @return bool
     */
    final public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * @param string $name
     *
     * @return Command|PluginIdentifiableCommand
     */
    public function getCommand(string $name): null|Command|PluginIdentifiableCommand
    {
        $command = $this->getServer()->getPluginCommand($name);
        if ($command === null or $command->getPlugin() !== $this) {
            $command = $this->getServer()->getPluginCommand(strtolower($this->description->getName()) . ":" . $name);
        }

        if ($command instanceof PluginIdentifiableCommand and $command->getPlugin() === $this) {
            return $command;
        } else {
            return null;
        }
    }

    /**
     * @param CommandSender $sender
     * @param Command       $command
     * @param string        $label
     * @param array         $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function isPhar(): bool
    {
        return substr($this->file, 0, 7) === "phar://";
    }

    /**
     * Gets an embedded resource on the plugin file.
     * WARNING: You must close the resource given using fclose()
     *
     * @param string $filename
     *
     * @return resource Resource data, or null
     */
    public function getResource(string $filename)
    {
        $filename = rtrim(str_replace("\\", "/", $filename), "/");
        if (file_exists($this->file . "resources/" . $filename)) {
            return fopen($this->file . "resources/" . $filename, "rb");
        }

        return null;
    }

    /**
     * @param string $filename
     * @param bool   $replace
     *
     * @return bool
     */
    public function saveResource(string $filename, bool $replace = false): bool
    {
        if (trim($filename) === "") {
            return false;
        }

        if (($resource = $this->getResource($filename)) === null) {
            return false;
        }

        $out = $this->dataFolder . $filename;
        if (!file_exists(dirname($out))) {
            mkdir(dirname($out), 0755, true);
        }

        if (file_exists($out) and $replace !== true) {
            return false;
        }

        $ret = stream_copy_to_stream($resource, $fp = fopen($out, "wb")) > 0;
        fclose($fp);
        fclose($resource);
        return $ret;
    }

    /**
     * Returns all the resources incrusted on the plugin
     * @return string[]
     */
    public function getResources(): array
    {
        $resources = [];
        if (is_dir($this->file . "resources/")) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->file . "resources/")) as $resource) {
                $resources[] = $resource;
            }
        }

        return $resources;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        if (!isset($this->config)) {
            $this->reloadConfig();
        }

        return $this->config;
    }

    public function saveConfig(): void
    {
        if ($this->getConfig()->save() === false) {
            $this->getLogger()->critical("Could not save config to " . $this->configFile);
        }
    }

    public function saveDefaultConfig(): bool
    {
        if (!file_exists($this->configFile)) {
            return $this->saveResource("config.yml", false);
        }
        return false;
    }

    public function reloadConfig(): void
    {
        $this->config = new Config($this->configFile);
        if (($configStream = $this->getResource("config.yml")) !== null) {
            $this->config->setDefaults(yaml_parse(config::fixYAMLIndexes(stream_get_contents($configStream))));
            fclose($configStream);
        }
    }

    /**
     * @return Server
     */
    final public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @return string
     */
    final public function getName(): string
    {
        return $this->description->getName();
    }

    /**
     * @return string
     */
    final public function getFullName(): string
    {
        return $this->description->getFullName();
    }

    protected function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return PluginLoader
     */
    public function getPluginLoader(): PluginLoader
    {
        return $this->loader;
    }

}
