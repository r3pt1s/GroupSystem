<?php

namespace r3pt1s\groupsystem\update;

use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use r3pt1s\groupsystem\GroupSystem;

final class UpdateChecker {
    use SingletonTrait;

    private array $data = [];

    public function __construct(private readonly bool $enabled = true) {
        self::setInstance($this);
        $this->check();
    }

    public function check(): void {
        if (!$this->isEnabled()) return;
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

    public function isEnabled(): bool {
        return $this->enabled;
    }
}