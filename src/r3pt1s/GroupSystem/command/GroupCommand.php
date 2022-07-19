<?php

namespace r3pt1s\GroupSystem\command;

use pocketmine\plugin\PluginOwned;
use r3pt1s\GroupSystem\form\MainForm;
use r3pt1s\GroupSystem\GroupSystem;
use r3pt1s\GroupSystem\util\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class GroupCommand extends Command implements PluginOwned {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->setPermission("groupsystem.group.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($sender->hasPermission($this->getPermission())) {
                $sender->sendForm(new MainForm());
            } else {
                $sender->sendMessage(Utils::parse("no_perms"));
            }
        }
        return true;
    }

    public function getOwningPlugin(): GroupSystem {
        return GroupSystem::getInstance();
    }
}