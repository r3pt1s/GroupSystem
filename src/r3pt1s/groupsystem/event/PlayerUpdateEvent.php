<?php

namespace r3pt1s\groupsystem\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerUpdateEvent extends PlayerEvent implements Cancellable {
    use CancellableTrait;

    public function __construct(
        Player $player,
        private string $nameTag,
        private string $displayName
    ) {
        $this->player = $player;
    }

    public function getNameTag(): string {
        return $this->nameTag;
    }

    public function setNameTag(string $nameTag): void {
        $this->nameTag = $nameTag;
    }

    public function getDisplayName(): string {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): void {
        $this->displayName = $displayName;
    }
}