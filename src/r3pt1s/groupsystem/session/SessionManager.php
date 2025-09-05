<?php

namespace r3pt1s\groupsystem\session;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class SessionManager {
    use SingletonTrait;

    /** @var array<Session> */
    private array $sessions = [];

    public function __construct() {
        self::setInstance($this);
    }

    public function tick(): void {
        foreach ($this->sessions as $session) {
            if ($session->hasFailed()) {
                $session->getPlayer()?->kick("§cFailed to load group data.\n§cPlease contact an administrator.");
                $this->destroy($session);
                continue;
            }

            if ($session->isInitialized()) {
                $session->tick();
            } else {
                if ($session->getLoadingTimeoutTick() <= Server::getInstance()->getTick()) {
                    $session->markAsFailed();
                }
            }
        }
    }

    public function init(Player $player): Session {
        return $this->sessions[$player->getName()] = new Session($player->getName());
    }

    public function destroy(Player|Session $player): void {
        unset($this->sessions[$player instanceof Player ? $player->getName() : $player->getUsername()]);
    }

    public function get(Player|string $player): Session {
        $player = $player instanceof Player ? $player->getName() : $player;
        return $this->sessions[$player] ??= new Session($player);
    }

    public function getSessions(): array {
        return $this->sessions;
    }
}