<?php

namespace r3pt1s\groupsystem\util;

use Exception;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use r3pt1s\groupsystem\GroupSystem;

final class Configuration {
    use SingletonTrait;

    public const PROVIDERS = ["json", "yml", "mysql"];

    private Config $config;
    private string $defaultGroup = "Player";
    private bool $doUpdateCheck = true;
    private bool $inGameDebugging = false;
    private string $provider = "json";
    private array $mysql = [
        "host" => "localhost",
        "port" => 3306,
        "username" => "root",
        "password" => "your_password",
        "database" => "your_database"
    ];
    private string $groupsPath;
    private string $playersPath;
    private string $messagesPath;

    public function __construct(Config $config) {
        self::setInstance($this);
        $this->config = $config;
        $this->load();
    }

    public function load(): void {
        if ($this->config->exists("Default-Group")) {
            $this->defaultGroup = $this->config->get("Default-Group", "Player");
        }

        if ($this->config->exists("Update-Check")) {
            $this->doUpdateCheck = boolval($this->config->get("Update-Check", true));
        }

        if ($this->config->exists("InGameDebugging")) {
            $this->inGameDebugging = boolval($this->config->get("InGameDebugging", true));
        }

        $this->provider = $this->config->get("provider", $this->provider);
        try {
            $this->mysql = $this->config->get("mysql", $this->mysql);
        } catch (Exception) {}

        if (!in_array(strtolower($this->provider), self::PROVIDERS)) {
            GroupSystem::getInstance()->getLogger()->warning("§cThe provided Provider §e" . strtolower($this->provider) . " §cdoesn't exists, using §eJSON §cinstead...");
            $this->provider = "json";
        }

        if ($this->config->exists("Groups-Path")) {
            if (@file_exists($this->config->get("Groups-Path"))) $this->groupsPath = $this->config->get("Groups-Path");
            else $this->groupsPath = GroupSystem::getInstance()->getDataFolder();
        } else $this->groupsPath = GroupSystem::getInstance()->getDataFolder();

        if ($this->config->exists("Players-Path")) {
            if (@file_exists($this->config->get("Players-Path"))) $this->playersPath = $this->config->get("Players-Path");
            else $this->playersPath = GroupSystem::getInstance()->getDataFolder() . "players/";
        } else $this->playersPath = GroupSystem::getInstance()->getDataFolder() . "players/";

        if ($this->config->exists("Messages-Path")) {
            if (@file_exists($this->config->get("Messages-Path"))) $this->messagesPath = $this->config->get("Messages-Path");
            else $this->messagesPath = GroupSystem::getInstance()->getDataFolder();
        } else $this->messagesPath = GroupSystem::getInstance()->getDataFolder();
    }

    public function getConfig(): Config {
        return $this->config;
    }

    public function getDefaultGroup(): string {
        return $this->defaultGroup;
    }

    public function isDoUpdateCheck(): bool {
        return $this->doUpdateCheck;
    }

    public function isInGameDebugging(): bool {
        return $this->inGameDebugging;
    }

    public function getProvider(): string {
        return $this->provider;
    }

    public function getMysql(): array {
        return $this->mysql;
    }

    public function getGroupsPath(): string {
        return $this->groupsPath;
    }

    public function getPlayersPath(): string {
        return $this->playersPath;
    }

    public function getMessagesPath(): string {
        return $this->messagesPath;
    }
}