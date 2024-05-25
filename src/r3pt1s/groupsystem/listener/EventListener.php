<?php

namespace r3pt1s\groupsystem\listener;

use DateTime;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\player\chat\LegacyRawChatFormatter;
use r3pt1s\groupsystem\event\GroupEditEvent;
use r3pt1s\groupsystem\event\GroupRemoveEvent;
use r3pt1s\groupsystem\event\GroupSetEvent;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\session\SessionManager;
use r3pt1s\groupsystem\util\Message;
use r3pt1s\groupsystem\util\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDisplayNameChangeEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class EventListener implements Listener {

    public function onLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        $username = $player->getName();
        GroupSystem::getInstance()->getProvider()->checkPlayer($username)->onCompletion(
            function(bool $exists) use($username, $player): void {
                if (!$exists) {
                    GroupSystem::getInstance()->getProvider()->createPlayer($username, function(bool $success) use($username, $player): void {
                        if ($success) {
                            SessionManager::getInstance()->init($player);
                        }
                    });
                } else {
                    SessionManager::getInstance()->init($player);
                }
            },
            function() use($player): void {
                $player->kick("§cAn internal error occurred. Please contact an administrator.");
            }
        );
    }

    public function onJoin(PlayerJoinEvent $event): void {
        Session::get($event->getPlayer())->onLoad(fn() => Session::get($event->getPlayer())->update());
    }

    public function onQuit(PlayerQuitEvent $event): void {
        SessionManager::getInstance()->destroy($event->getPlayer());
    }

    public function onDisplayNameChange(PlayerDisplayNameChangeEvent $event): void {
        GroupSystem::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                $player->getNetworkSession()->syncPlayerList(Server::getInstance()->getOnlinePlayers());
            }
        }), 1);
    }

    public function onChat(PlayerChatEvent $event): void {
        if ($event->isCancelled()) return;
        $event->setFormatter(new LegacyRawChatFormatter(
            str_replace(["{name}", "{msg}", "{message}"], [$event->getPlayer()->getName(), $event->getMessage(), $event->getMessage()], Session::get($event->getPlayer())->getGroup()->getGroup()->getChatFormat())
        ));
    }

    public function onSet(GroupSetEvent $event): void {
        $player = Server::getInstance()->getPlayerExact($event->getUsername());
        if ($player !== null) {
            $expireString = (string) Message::RAW_NEVER();
            if ($event->getGroup()->getExpireDate() instanceof DateTime) $expireString = Utils::diffString(new DateTime("now"), $event->getGroup()->getExpireDate());
            $player->sendMessage(Message::GROUP_CHANGED()->parse([$event->getGroup()->getGroup()->getColorCode() . $event->getGroup()->getGroup()->getName(), $expireString]));
        }
    }

    public function onRemove(GroupRemoveEvent $event): void {
        foreach (array_filter(SessionManager::getInstance()->getSessions(), fn(Session $session) => $session->isLoaded() && $session->getPlayer() !== null) as $session) {
            if ($session->getGroup()->getGroup()->getName() == $event->getGroup()->getName()) {
                $session->nextGroup();
            }
        }
    }

    public function onEdit(GroupEditEvent $event): void {
        foreach (array_filter(SessionManager::getInstance()->getSessions(), fn(Session $session) => $session->isLoaded() && $session->getPlayer() !== null) as $session) {
            if ($session->getGroup()->getGroup()->getName() == $event->getGroup()->getName()) {
                $session->update();
            }
        }
    }
}