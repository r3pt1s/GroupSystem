<?php

namespace r3pt1s\GroupSystem\util;

use pocketmine\utils\Config;
use r3pt1s\GroupSystem\GroupSystem;

class Configuration {

    private static self $instance;
    private Config $config;
    private string $defaultGroup = "Player";
    private bool $doUpdateCheck = true;
    private string $groupsPath;
    private string $playersPath;
    private string $messagesPath;

    public function __construct(Config $config) {
        self::$instance = $this;
        $this->config = $config;
        $this->load();
    }

    public function load() {
        if ($this->config->exists("Default-Group")) {
            $this->defaultGroup = $this->config->get("Default-Group", "Player");
        }

        if ($this->config->exists("Update-Check")) {
            $this->doUpdateCheck = boolval($this->config->get("Update-Check", true));
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

    public function getGroupsPath(): string {
        return $this->groupsPath;
    }

    public function getPlayersPath(): string {
        return $this->playersPath;
    }

    public function getMessagesPath(): string {
        return $this->messagesPath;
    }

    public static function getInstance(): Configuration {
        return self::$instance;
    }
}