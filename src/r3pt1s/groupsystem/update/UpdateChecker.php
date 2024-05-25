<?php

namespace r3pt1s\groupsystem\update;

use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use r3pt1s\groupsystem\GroupSystem;

class UpdateChecker {
    use SingletonTrait;

    private array $data = [];

    public function __construct(private readonly bool $doUpdateCheck = true) {
        self::setInstance($this);
        $this->check();
    }

    public function check(): void {
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