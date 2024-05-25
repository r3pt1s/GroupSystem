<?php

namespace r3pt1s\groupsystem\command;

use JetBrains\PhpStorm\Pure;
use pocketmine\plugin\PluginOwned;
use r3pt1s\groupsystem\form\MainForm;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\util\Message;
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
            if ($this->testPermissionSilent($sender)) {
                $sender->sendForm(new MainForm());
            } else {
                $sender->sendMessage(Message::NO_PERM());
            }
        }
        return true;
    }

    #[Pure] public function getOwningPlugin(): GroupSystem {
        return GroupSystem::getInstance();
    }
}