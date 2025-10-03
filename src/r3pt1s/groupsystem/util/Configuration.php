<?php

namespace r3pt1s\groupsystem\util;

use Exception;
use JsonException;
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
    private int $sessionTimeout = 5;
    private array $groupHierarchy = [];

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

        if ($this->config->exists("Session-Timeout")) {
            if (is_numeric($this->config->get("Session-Timeout", $this))) $this->sessionTimeout = intval($this->config->get("Session-Timeout", $this->sessionTimeout));
            if ($this->sessionTimeout <= 0) $this->sessionTimeout = 5;
        } else {
            $this->config->set("Session-Timeout", $this->sessionTimeout);
            try {
                $this->config->save();
            } catch (JsonException $e) {
                GroupSystem::getInstance()->getLogger()->error("§cFailed to save session timeout.");
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }

        if ($this->config->exists("Group-Hierarchy")) {
            try {
                $this->groupHierarchy = $this->config->get("Group-Hierarchy", $this->groupHierarchy);
            } catch (Exception) {}
        } else {
            $this->config->set("Group-Hierarchy", $this->groupHierarchy);
            try {
                $this->config->save();
            } catch (JsonException $e) {
                GroupSystem::getInstance()->getLogger()->error("§cFailed to save group hierarchy.");
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }
    }

    public function setDefaultGroup(string $defaultGroup): void {
        $this->defaultGroup = $defaultGroup;
        $this->config->set("Default-Group", $defaultGroup);
        try {
            $this->config->save();
        } catch (JsonException $e) {
            GroupSystem::getInstance()->getLogger()->error("§cFailed to save default group.");
            GroupSystem::getInstance()->getLogger()->logException($e);
        }
    }

    public function addGroupToHierarchy(string $group): void {
        if (empty($this->groupHierarchy)) return;
        if (in_array($group, $this->groupHierarchy)) return;
        $this->groupHierarchy[] = $group;
        $this->config->set("Group-Hierarchy", $this->groupHierarchy);
        try {
            $this->config->save();
        } catch (JsonException $e) {
            GroupSystem::getInstance()->getLogger()->error("§cFailed to save group hierarchy.");
            GroupSystem::getInstance()->getLogger()->logException($e);
        }
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

    public function getSessionTimeout(): int {
        return $this->sessionTimeout;
    }

    public function getGroupHierarchy(): array {
        return $this->groupHierarchy;
    }
}