<?php

namespace r3pt1s\GroupSystem\task;

use r3pt1s\GroupSystem\group\GroupManager;
use r3pt1s\GroupSystem\player\PlayerGroup;
use r3pt1s\GroupSystem\player\PlayerGroupManager;
use r3pt1s\GroupSystem\util\Utils;
use pocketmine\scheduler\Task;

class GroupExpireTask extends Task {

    public function onRun(): void {
        foreach (PlayerGroupManager::getInstance()->getAllPlayersConfig() as $player => $config) {
            $group = PlayerGroupManager::getInstance()->getGroup($player);
            if ($group->hasExpired()) {
                PlayerGroupManager::getInstance()->nextGroup($player);
            } else {
                $current = PlayerGroupManager::getInstance()->getGroup($player);
                $default = GroupManager::getInstance()->getDefaultGroup();
                $next = PlayerGroupManager::getInstance()->getNextHighestGroup($player);
                if (count(PlayerGroupManager::getInstance()->getGroups($player)) > 0) {
                    if ($current->getGroup()->getName() == $default->getName()) {
                        if ($current->getExpireDate() === null) {
                            if ($next->getGroup()->isHigher($current->getGroup())) {
                                PlayerGroupManager::getInstance()->nextGroup($player);
                            }
                        }
                    } else {
                        if ($next->getGroup()->isHigher($current->getGroup())) {
                            if ($current->getExpireDate() === null) {
                                PlayerGroupManager::getInstance()->removeGroup($player, $next);
                                PlayerGroupManager::getInstance()->addGroup($player, $current);
                                PlayerGroupManager::getInstance()->setGroup($player, $next);
                            } else {
                                PlayerGroupManager::getInstance()->removeGroup($player, $next);
                                $timeString = Utils::diffString(new \DateTime("now"), $current->getExpireDate(), true);
                                PlayerGroupManager::getInstance()->addGroup($player, new PlayerGroup($current->getGroup(), $current->getPriority(), $timeString));
                                PlayerGroupManager::getInstance()->setGroup($player, $next);
                            }
                        }
                    }
                }
            }
        }
    }
}