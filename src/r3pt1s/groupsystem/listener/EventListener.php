<?php

namespace r3pt1s\groupsystem\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDisplayNameChangeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\session\SessionManager;

final class EventListener implements Listener {

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

    public function onDataPacketSend(DataPacketSendEvent $event): void {
        foreach ($event->getPackets() as $packet) {
            if ($packet instanceof AvailableCommandsPacket) {
                if (!isset($packet->commandData["group"])) continue;
                $packet->commandData["group"]->overloads = [];
            }
        }
    }
}