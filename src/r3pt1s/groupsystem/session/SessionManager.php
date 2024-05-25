<?php

namespace r3pt1s\groupsystem\session;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class SessionManager {
    use SingletonTrait;

    /** @var array<Session> */
    private array $sessions = [];

    public function __construct() {
        self::setInstance($this);
    }

    public function init(Player $player): Session {
        return $this->sessions[$player->getName()] = new Session($player->getName());
    }

    public function destroy(Player $player): void {
        unset($this->sessions[$player->getName()]);
    }

    public function get(Player|string $player): Session {
        $player = $player instanceof Player ? $player->getName() : $player;
        return $this->sessions[$player] ??= new Session($player);
    }

    public function getSessions(): array {
        return $this->sessions;
    }
}