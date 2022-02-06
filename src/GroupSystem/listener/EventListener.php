<?php

namespace GroupSystem\listener;

use GroupSystem\group\GroupManager;
use GroupSystem\utils\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;

class EventListener implements Listener {

    public function onJoin(PlayerJoinEvent $event) {
        if (!GroupManager::getInstance()->createPlayer($event->getPlayer())) {
            $event->getPlayer()->kick(Utils::parse("error"));
            return;
        }

        GroupManager::getInstance()->setAttachment($event->getPlayer());
        GroupManager::getInstance()->updatePlayer($event->getPlayer());
    }

    public function onChat(PlayerChatEvent $event) {
        if (($group = GroupManager::getInstance()->getGroup($event->getPlayer())) !== null) $event->setFormat(str_replace(["{name}", "{msg}", "{message}"], [$event->getPlayer()->getName(), $event->getMessage(), $event->getMessage()], $group->getChatFormat()));
    }
}