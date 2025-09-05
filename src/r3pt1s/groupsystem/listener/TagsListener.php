<?php

namespace r3pt1s\groupsystem\listener;

use DateTime;
use Ifera\ScoreHud\event\PlayerTagsUpdateEvent;
use Ifera\ScoreHud\event\TagsResolveEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use pocketmine\event\Listener;
use pocketmine\Server;
use r3pt1s\groupsystem\event\group\GroupEditEvent;
use r3pt1s\groupsystem\event\group\GroupRemoveEvent;
use r3pt1s\groupsystem\event\player\PlayerGroupSetEvent;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\session\SessionManager;
use r3pt1s\groupsystem\util\Message;
use r3pt1s\groupsystem\util\Utils;

final class TagsListener implements Listener {

    public function onResolve(TagsResolveEvent $event): void {
        $tag = $event->getTag();
        $group = Session::get($event->getPlayer())->getGroup();
        if ($group === null) return;

        switch ($tag->getName()) {
            case "groupsystem.group":
                $tag->setValue($group->getGroup()->getColorCode() . $group->getGroup()->getName());
                break;
            case "groupsystem.group.name":
                $tag->setValue($group->getGroup()->getName());
                break;
            case "groupsystem.group.expire":
                $expireString = (string) Message::RAW_NEVER();
                if ($group->getExpireDate() instanceof DateTime) $expireString = Utils::diffString(new DateTime("now"), $group->getExpireDate());
                $tag->setValue($expireString);
                break;
        }
    }

    public function onSet(PlayerGroupSetEvent $event): void {
        $player = Server::getInstance()->getPlayerExact($event->getUsername());
        $group = $event->getGroup();

        $expireString = (string) Message::RAW_NEVER();
        if ($group->getExpireDate() instanceof DateTime) $expireString = Utils::diffString(new DateTime("now"), $group->getExpireDate());

        if ($player !== null) {
            $ev = new PlayerTagsUpdateEvent(
                $player,
                [
                    new ScoreTag("groupsystem.group", $group->getGroup()->getColorCode() . $group->getGroup()->getName()),
                    new ScoreTag("groupsystem.group.name", $group->getGroup()->getName()),
                    new ScoreTag("groupsystem.group.expire", $expireString)
                ]
            );
            $ev->call();
        }
    }

    public function onEdit(GroupEditEvent $event): void {
        $group = $event->getGroup();

        foreach (array_filter(SessionManager::getInstance()->getSessions(), fn(Session $session) => $session->isInitialized() && $session->getPlayer() !== null) as $session) {
            if ($session->getGroup()->getGroup()->getName() == $group->getName()) {
                $ev = new PlayerTagsUpdateEvent(
                    $session->getPlayer(),
                    [new ScoreTag("groupsystem.group", $event->getNewData()["colorCode"] . $group->getName())]
                );
                $ev->call();
            }
        }
    }

    public function onRemove(GroupRemoveEvent $event): void {
        $group = $event->getGroup();
        $default = GroupManager::getInstance()->getDefaultGroup();

        foreach (array_filter(SessionManager::getInstance()->getSessions(), fn(Session $session) => $session->isInitialized() && $session->getPlayer() !== null) as $session) {
            if ($session->getGroup()->getGroup()->getName() == $group->getName()) {
                $ev = new PlayerTagsUpdateEvent(
                    $session->getPlayer(),
                    [
                        new ScoreTag("groupsystem.group", $default->getColorCode() . $default->getName()),
                        new ScoreTag("groupsystem.group.name", $default->getName()),
                        new ScoreTag("groupsystem.group.expire", Message::RAW_NEVER())
                    ]
                );
                $ev->call();
            }
        }
    }
}