<?php

namespace GroupSystem\commands;

use GroupSystem\group\Group;
use GroupSystem\group\GroupManager;
use GroupSystem\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class GroupCommand extends Command {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = [])
    {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->setPermission("group.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender->hasPermission($this->getPermission())) {
            if (isset($args[0])) {
                if (strtolower($args[0]) == "create") {
                    if (isset($args[1])) {
                        if (!GroupManager::getInstance()->isGroupExisting($args[1])) {
                            $sender->sendMessage(Utils::parse("successfulCreated", [$args[1]]));
                            GroupManager::getInstance()->createGroup(new Group($args[1]));
                        } else $sender->sendMessage(Utils::parse("alreadyExists", [$args[1]]));
                    } else $sender->sendMessage(Utils::parse("createGroupUsage"));
                } else if (strtolower($args[0]) == "remove") {
                    if (isset($args[1])) {
                        if (($group = GroupManager::getInstance()->getGroupByName($args[1])) !== null) {
                            $sender->sendMessage(Utils::parse("successfulRemoved", [$group->getName()]));
                            GroupManager::getInstance()->removeGroup($group);
                        } else $sender->sendMessage(Utils::parse("doesntExists", [$args[1]]));
                    } else $sender->sendMessage(Utils::parse("removeGroupUsage"));
                } else if (strtolower($args[0]) == "set") {
                    if (isset($args[1]) && isset($args[2])) {
                        if (GroupManager::getInstance()->isPlayerExisting($args[1])) {
                            if (($group = GroupManager::getInstance()->getGroupByName($args[2])) !== null) {
                                $sender->sendMessage(Utils::parse("successfulSet", [$group->getName(), $args[1]]));
                                GroupManager::getInstance()->setGroup($args[1], $group);
                            } else $sender->sendMessage(Utils::parse("doesntExists", [$args[2]]));
                        } else $sender->sendMessage(Utils::parse("notFound", [$args[1]]));
                    } else $sender->sendMessage(Utils::parse("setGroupUsage"));
                } else if (strtolower($args[0]) == "list") {
                    $groups = [];
                    foreach (GroupManager::getInstance()->getGroups() as $group) $groups[] = $group->getColorCode() . $group->getName();

                    $sender->sendMessage(Utils::parse("groupList", [implode("§8, §e", $groups)]));
                } else if (strtolower($args[0]) == "reload") {
                    GroupManager::getInstance()->reload();
                    $sender->sendMessage(Utils::parse("successfulReloaded"));
                } else if (strtolower($args[0]) == "help") {
                    $sender->sendMessage(Utils::parse("helpMessage"));
                } else {
                    $sender->sendMessage(Utils::parse("helpUsage"));
                }
            } else {
                $sender->sendMessage(Utils::parse("helpUsage"));
            }
        } else {
            $sender->sendMessage(Utils::parse("noPerms"));
        }
        return true;
    }
}