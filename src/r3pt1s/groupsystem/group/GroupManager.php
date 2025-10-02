<?php

namespace r3pt1s\groupsystem\group;

use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\utils\SingletonTrait;
use r3pt1s\groupsystem\event\group\GroupCreateEvent;
use r3pt1s\groupsystem\event\group\GroupEditEvent;
use r3pt1s\groupsystem\event\group\GroupRemoveEvent;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\provider\impl\JSONProvider;
use r3pt1s\groupsystem\provider\impl\YAMLProvider;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\session\SessionManager;
use r3pt1s\groupsystem\util\Configuration;

final class GroupManager {
    use SingletonTrait;

    /** @var array<Group> */
    private array $groups = [];

    public function __construct() {
        self::setInstance($this);

        $this->load();
    }

    private function load(): Promise {
        $resolver = new PromiseResolver();
        GroupSystem::getInstance()->getProvider()->getAllGroups()->onCompletion(function(array $groups) use($resolver): void {
            $this->groups = $groups;
            $resolver->resolve($this->groups);
            $this->createDefaults();
        }, function() use($resolver): void {
            $resolver->reject();
            GroupSystem::getInstance()->getLogger()->warning("§cFailed to fetch groups");
        });

        return $resolver->getPromise();
    }

    public function createGroup(Group $group): void {
        if (!isset($this->groups[$group->getName()])) {
            GroupSystem::getInstance()->getProvider()->createGroup($group);
            ($ev = new GroupCreateEvent($group))->call();
            if ($ev->isCancelled()) {
                GroupSystem::getInstance()->getLogger()->notice("Cancelled the creation of group {$group->getName()}: Event cancelled");
                return;
            }

            $this->groups[$group->getName()] = $group;
        }
    }

    public function removeGroup(Group $group): void {
        GroupSystem::getInstance()->getProvider()->removeGroup($group);
        ($ev = new GroupRemoveEvent($group))->call();
        if ($ev->isCancelled()) {
            GroupSystem::getInstance()->getLogger()->notice("Cancelled the removal of group {$group->getName()}: Event cancelled");
            return;
        }

        if (isset($this->groups[$group->getName()])) unset($this->groups[$group->getName()]);

        foreach (array_filter(SessionManager::getInstance()->getSessions(), fn(Session $session) => $session->isInitialized() && $session->getPlayer() !== null) as $session) {
            if ($session->getGroup()->getGroup()->getName() == $group->getName()) {
                $session->nextGroup();
            }
        }
    }

    public function editGroup(Group $group, string $nameTag, string $displayName, string $chatFormat, string $colorCode, array $permissions): void {
        $oldGroupData = clone $group;
        $newGroupData = clone $group;
        $newGroupData->setNameTag($nameTag)
            ->setDisplayName($displayName)
            ->setChatFormat($chatFormat)
            ->setColorCode($colorCode)
            ->setPermissions($permissions);

        ($ev = new GroupEditEvent($oldGroupData, $newGroupData))->call();
        if ($ev->isCancelled()) {
            GroupSystem::getInstance()->getLogger()->notice("Cancelled the editing of group {$group->getName()}: Event cancelled");
            return;
        }

        $group->apply($ev->getNewGroupData());
        GroupSystem::getInstance()->getProvider()->editGroup($group, $group->write());
        foreach (array_filter(SessionManager::getInstance()->getSessions(), fn(Session $session) => $session->isInitialized() && $session->getPlayer() !== null) as $session) {
            if ($session->getGroup()->getGroup()->getName() == $group->getName()) {
                $session->update();
            }
        }
    }

    private function createDefaults(): void {
        if (!$this->checkGroup(Configuration::getInstance()->getDefaultGroup())) $this->createGroup(new Group(Configuration::getInstance()->getDefaultGroup()));
    }

    public function getGroup(string $name): ?Group {
        return $this->groups[$name] ?? null;
    }

    public function getDefaultGroup(): ?Group {
        return $this->getGroup(Configuration::getInstance()->getDefaultGroup());
    }

    public function checkGroup(string $name): bool {
        return isset($this->groups[$name]);
    }

    public function getGroups(): array {
        return $this->groups;
    }

    public function reload(): Promise {
        $provider = GroupSystem::getInstance()->getProvider();
        $this->groups = [];
        if ($provider instanceof YAMLProvider || $provider instanceof JSONProvider) $provider->getFile()?->reload();
        return $this->load();
    }
}