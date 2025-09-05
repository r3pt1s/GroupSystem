<?php

namespace r3pt1s\groupsystem\task;

use pocketmine\scheduler\Task;
use r3pt1s\groupsystem\session\SessionManager;

final class SessionTickTask extends Task {

    public function onRun(): void {
        SessionManager::getInstance()->tick();
    }
}