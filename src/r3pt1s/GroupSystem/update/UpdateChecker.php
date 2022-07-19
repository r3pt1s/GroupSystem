<?php

namespace r3pt1s\GroupSystem\update;

use pocketmine\Server;
use r3pt1s\GroupSystem\GroupSystem;

class UpdateChecker {

    private static self $instance;
    private bool $doUpdateCheck;
    private array $data = [];

    public function __construct(bool $doUpdateCheck = true) {
        self::$instance = $this;
        $this->doUpdateCheck = $doUpdateCheck;
        $this->check();
    }

    public function check() {
        if (!$this->isDoUpdateCheck()) return;
       Server::getInstance()->getAsyncPool()->submitTask(new AsyncUpdateCheckTask());
    }

    public function isOutdated(): ?bool {
        return $this->data["outdated"] ?? null;
    }

    public function isUpToDate(): bool {
        return !$this->isOutdated();
    }

    public function getNewestVersion(): ?string {
        return $this->data["newest_version"] ?? null;
    }

    public function getCurrentVersion(): string {
        return GroupSystem::getInstance()->getDescription()->getVersion();
    }

    public function setData(array $data): void {
        $this->data = $data;
    }

    public function getData(): array {
        return $this->data;
    }

    public function isDoUpdateCheck(): bool {
        return $this->doUpdateCheck;
    }

    public static function getInstance(): UpdateChecker {
        return self::$instance;
    }
}