<?php

namespace r3pt1s\groupsystem\listener;

use Ifera\ScoreHud\event\PlayerTagsUpdateEvent;
use Ifera\ScoreHud\event\TagsResolveEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use pocketmine\event\Listener;
use pocketmine\Server;
use r3pt1s\groupsystem\event\GroupEditEvent;
use r3pt1s\groupsystem\event\GroupRemoveEvent;
use r3pt1s\groupsystem\event\GroupSetEvent;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\session\SessionManager;
use r3pt1s\groupsystem\util\Utils;

class TagsListener implements Listener {

    public function onResolve(TagsResolveEvent $event) {
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
                $expireString = Utils::parse("raw_never");
                if ($group->getExpireDate() instanceof \DateTime) $expireString = Utils::diffString(new \DateTime("now"), $group->getExpireDate());
                $tag->setValue($expireString);
                break;
        }
    }

    public function onSet(GroupSetEvent $event) {
        $player = Server::getInstance()->getPlayerExact($event->getUsername());
        $group = $event->getGroup();

        $expireString = Utils::parse("raw_never");
        if ($group->getExpireDate() instanceof \DateTime) $expireString = Utils::diffString(new \DateTime("now"), $group->getExpireDate());

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

    public function onEdit(GroupEditEvent $event) {
        $group = $event->getGroup();

        foreach (array_filter(SessionManager::getInstance()->getSessions(), fn(Session $session) => $session->isLoaded() && $session->getPlayer() !== null) as $session) {
            if ($session->getGroup()->getGroup()->getName() == $group->getName()) {
                $ev = new PlayerTagsUpdateEvent(
                    $session->getPlayer(),
                    [new ScoreTag("groupsystem.group", $event->getNewData()["colorCode"] . $group->getName())]
                );
                $ev->call();
            }
        }
    }

    public function onRemove(GroupRemoveEvent $event) {
        $group = $event->getGroup();
        $default = GroupManager::getInstance()->getDefaultGroup();

        foreach (array_filter(SessionManager::getInstance()->getSessions(), fn(Session $session) => $session->isLoaded() && $session->getPlayer() !== null) as $session) {
            if ($session->getGroup()->getGroup()->getName() == $group->getName()) {
                $ev = new PlayerTagsUpdateEvent(
                    $session->getPlayer(),
                    [
                        new ScoreTag("groupsystem.group", $default->getColorCode() . $default->getName()),
                        new ScoreTag("groupsystem.group.name", $default->getName()),
                        new ScoreTag("groupsystem.group.expire", Utils::parse("raw_never"))
                    ]
                );
                $ev->call();
            }
        }
    }
}