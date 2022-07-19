<?php

namespace r3pt1s\GroupSystem\listener;

use r3pt1s\GroupSystem\event\GroupEditEvent;
use r3pt1s\GroupSystem\event\GroupRemoveEvent;
use r3pt1s\GroupSystem\event\GroupSetEvent;
use r3pt1s\GroupSystem\GroupSystem;
use r3pt1s\GroupSystem\player\PlayerGroupManager;
use r3pt1s\GroupSystem\util\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDisplayNameChangeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class EventListener implements Listener {

    public function onJoin(PlayerJoinEvent $event) {
        $player = PlayerGroupManager::getInstance()->loadPlayer($event->getPlayer()->getName());
        $player->setAttachment($event->getPlayer()->addAttachment(GroupSystem::getInstance()));
        $player->update();
    }

    public function onQuit(PlayerQuitEvent $event) {
        PlayerGroupManager::getInstance()->unloadPlayer($event->getPlayer()->getName());
    }

    public function onDisplayNameChange(PlayerDisplayNameChangeEvent $event) {
        GroupSystem::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                $player->getNetworkSession()->syncPlayerList(Server::getInstance()->getOnlinePlayers());
            }
        }), 1);
    }

    public function onChat(PlayerChatEvent $event) {
        if ($event->isCancelled()) return;
        $event->setFormat(str_replace(["{name}", "{msg}", "{message}"], [$event->getPlayer()->getName(), $event->getMessage(), $event->getMessage()], PlayerGroupManager::getInstance()->getGroup($event->getPlayer()->getName())->getGroup()->getChatFormat()));
    }

    public function onSet(GroupSetEvent $event) {
        $player = Server::getInstance()->getPlayerExact($event->getPlayer());
        if ($player !== null) {
            $expireString = Utils::parse("raw_never");
            if ($event->getGroup()->getExpireDate() instanceof \DateTime) $expireString = Utils::diffString(new \DateTime("now"), $event->getGroup()->getExpireDate());
            else if (is_string($event->getGroup()->getExpireDate())) $expireString = Utils::diffString(new \DateTime("now"), Utils::convertStringToDateFormat($event->getGroup()->getExpireDate()));
            $player->sendMessage(Utils::parse("group_changed", [$event->getGroup()->getGroup()->getColorCode() . $event->getGroup()->getGroup()->getName(), $expireString]));
        }
    }

    public function onRemove(GroupRemoveEvent $event) {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if (PlayerGroupManager::getInstance()->getGroup($player->getName())->getGroup()->getName() == $event->getGroup()->getName()) {
                PlayerGroupManager::getInstance()->nextGroup($player->getName());
            }
        }
    }

    public function onEdit(GroupEditEvent $event) {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if (PlayerGroupManager::getInstance()->getGroup($player->getName())->getGroup()->getName() == $event->getGroup()->getName()) {
                PlayerGroupManager::getInstance()->update($player);
            }
        }
    }
}