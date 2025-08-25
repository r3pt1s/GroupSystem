<?php

namespace r3pt1s\groupsystem\command;

use pocketmine\plugin\PluginOwned;
use r3pt1s\groupsystem\form\MainForm;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\util\Message;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class GroupCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("group", "Group Command", "", ["rank"]);
        $this->setPermission("groupsystem.group.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                $sender->sendForm(new MainForm());
            } else {
                $sender->sendMessage(Message::NO_PERM());
            }
        }
        return true;
    }

    public function getOwningPlugin(): GroupSystem {
        return GroupSystem::getInstance();
    }
}