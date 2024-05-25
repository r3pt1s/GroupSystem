<?php

namespace r3pt1s\groupsystem\task;

use pocketmine\scheduler\Task;
use r3pt1s\groupsystem\session\SessionManager;

class SessionTickTask extends Task {

    public function onRun(): void {
        foreach (SessionManager::getInstance()->getSessions() as $session) $session->tick();
    }
}