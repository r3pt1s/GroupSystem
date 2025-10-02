<?php

namespace r3pt1s\groupsystem\form;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use r3pt1s\forms\builder\CustomFormBuilder;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\builder\ModalFormBuilder;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\menu\MenuForm;
use r3pt1s\forms\type\misc\CustomFormResponse;
use r3pt1s\forms\type\modal\ModalForm;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\util\Configuration;
use r3pt1s\groupsystem\util\Message;
use r3pt1s\groupsystem\util\Utils;

final class GroupFormManager {

    public static function mainForm(string $message = ""): MenuForm {
        $groupsCount = count(GroupManager::getInstance()->getGroups());
        return MenuFormBuilder::create()
            ->title(Message::MAIN_UI_TITLE())
            ->body($message)
            ->button(Message::MAIN_UI_MANAGE_PLAYERS(), clickClosure: fn(Player $player) => $player->sendForm(PlayerFormManager::choosePlayerForm()))
            ->divider()
            ->label(Message::MAIN_UI_TEXT()->parse([$groupsCount, implode("§8, §e", array_map(fn(Group $group) => $group->getFancyName(), GroupManager::getInstance()->getGroups()))]))
            ->button(Message::MANAGE_GROUPS_UI_CREATE_GROUP(), clickClosure: fn(Player $player) => $player->sendForm(self::createGroupForm()))
            ->button(Message::MANAGE_GROUPS_UI_EDIT_GROUP(), clickClosure: fn(Player $player) => $player->sendForm(self::editGroupChooseForm()))
            ->button(Message::MANAGE_GROUPS_UI_REMOVE_GROUP(), clickClosure: fn(Player $player) => $player->sendForm(self::removeGroupForm()))
            ->button(Message::MANAGE_GROUPS_UI_SEE_GROUPS(), clickClosure: fn(Player $player) => $player->sendForm(self::seeGroupsForm()))
            ->button(Message::MANAGE_GROUPS_UI_RELOAD_GROUPS(), clickClosure: function (Player $player): void {
                GroupManager::getInstance()->reload()->onCompletion(
                    fn() => $player->sendForm(self::mainForm(Message::GROUPS_RELOADED())),
                    fn() => $player->sendForm(self::mainForm(Message::GROUPS_RELOAD_FAILED()))
                );
            })
            ->build();
    }

    public static function createGroupForm(): CustomForm {
        return CustomFormBuilder::create()
            ->title(Message::CREATE_GROUP_UI_TITLE())
            ->label(Message::CREATE_GROUP_UI_TEXT())
            ->input("name", Message::CREATE_GROUP_UI_NAME(), "Admin")
            ->input("nameTag", Message::CREATE_GROUP_UI_NAME_TAG())
            ->input("displayName", Message::CREATE_GROUP_UI_NAME_TAG())
            ->input("colorCode", Message::CREATE_GROUP_UI_NAME_TAG())
            ->input("chatFormat", Message::CREATE_GROUP_UI_NAME_TAG())
            ->divider()
            ->label(Message::CREATE_GROUP_UI_PERMISSIONS_TIP())
            ->input("permissions", Message::CREATE_GROUP_UI_PERMISSIONS())
            ->onSubmit(function (Player $player, CustomFormResponse $response): void {
                $name = TextFormat::clean(trim($response->getString("name")));
                $nameTag = $response->getString("nameTag");
                $displayName = $response->getString("displayName");
                $colorCode = $response->getString("colorCode");
                $chatFormat = $response->getString("chatFormat");
                $permissions = explode(";", $response->getString("permissions"));

                if ($name !== "") {
                    if (!GroupManager::getInstance()->checkGroup($name)) {
                        GroupManager::getInstance()->createGroup($group = new Group($name, $nameTag, $displayName, $chatFormat, $colorCode, $permissions));
                        $player->sendForm(self::mainForm(Message::GROUP_CREATED()->parse([$group->getFancyName()])));
                    } else $player->sendForm(self::mainForm(Message::GROUP_ALREADY_EXISTS()->parse([$name])));
                } else $player->sendForm(self::mainForm(Message::PROVIDE_GROUP_NAME()));
            })
            ->build();
    }
    
    public static function editGroupChooseForm(): MenuForm {
        $builder = MenuFormBuilder::create()
            ->title(Message::EDIT_GROUP_CHOOSE_UI_TITLE());

        foreach (GroupManager::getInstance()->getGroups() as $group) {
            $builder->button($group->getFancyName() . ($group->isDefault() ? " §8(§l§cD§r§8)" : ""), clickClosure: fn(Player $player) => $player->sendForm(self::editGroupForm($group)));
        }

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::mainForm()));
        
        return $builder->build();
    }

    public static function editGroupForm(Group $group, string $message = ""): MenuForm {
        $finalContent = ($message == "" ? "" : $message . "\n\n");
        $finalContent .= "§7Name: " . $group->getFancyName() . "\n";
        $finalContent .= "§7DisplayName: " . $group->getDisplayName() . "\n";
        $finalContent .= "§7NameTag: " . $group->getNameTag() . "\n";
        $finalContent .= "§7ChatFormat: " . $group->getChatFormat() . "\n";
        $finalContent .= "§7Total Permission Entries: §e" . count($group->getPermissions()) . " entries\n";
        $finalContent .= "§7DefaultGroup?: " . ($group->isDefault() ? "§aYes" : "§cNo");

        return MenuFormBuilder::create()
            ->title(Message::EDIT_GROUP_UI_TITLE())
            ->body($finalContent)
            ->button("§bEdit Metadata", clickClosure: fn(Player $player) => $player->sendForm(self::editGroupMetadataForm($group)))
            ->button("§eEdit Permissions", clickClosure: fn(Player $player) => $player->sendForm(self::editGroupPermissionsForm($group)))
            ->button("§aSet as default group", clickClosure: function(Player $player) use($group): void {
                Configuration::getInstance()->setDefaultGroup($group->getName());
                $player->sendForm(self::editGroupForm($group, "§7This group has been set to the default group."));
            })
            ->onCancel(fn(Player $player) => $player->sendForm(self::mainForm()))
            ->build();
    }

    public static function editGroupMetadataForm(Group $group): CustomForm {
        return CustomFormBuilder::create()
            ->title("§bMetadata")
            ->input("nameTag", "§7Nametag", $group->getNameTag(), $group->getNameTag())
            ->input("displayName", "§7Displayname", $group->getDisplayName(), $group->getDisplayName())
            ->input("chatFormat", "§7Chatformat", $group->getChatFormat(), $group->getChatFormat())
            ->input("colorCode", "§7Colorcode", $group->getColorCode(), $group->getColorCode())
            ->onCancel(fn(Player $player) => $player->sendForm(self::editGroupForm($group)))
            ->onSubmit(function (Player $player, CustomFormResponse $response) use($group): void {
                $nameTag = $response->getString("nameTag");
                $displayName = $response->getString("displayName");
                $chatFormat = $response->getString("chatFormat");
                $colorCode = $response->getString("colorCode");

                GroupManager::getInstance()->editGroup($group, $nameTag, $displayName, $chatFormat, $colorCode, $group->getPermissions());
                $player->sendForm(self::editGroupForm($group, "§7Edited the metadata of this group."));
            })
            ->build();
    }

    public static function editGroupPermissionsForm(Group $group): MenuForm {
        return MenuFormBuilder::create()
            ->title("§eEdit Permissions")
            ->body("§7Permissions§8: §a" . implode("§8, §e", array_merge(
                    array_map(fn(string $perm) => "§a" . $perm, $group->getGrantedPermissions()),
                    array_map(fn(string $perm) => "§c" . $perm, $group->getRevokedPermissions())
                )))
            ->button("§aGrant", clickClosure: fn(Player $player) => $player->sendForm(self::editGroupPermissionsGrantForm($group)))
            ->button("§cRevoke", clickClosure: fn(Player $player) => $player->sendForm(self::editGroupPermissionsRevokeForm($group)))
            ->divider()
            ->button("§4Remove", clickClosure: fn(Player $player) => $player->sendForm(self::editGroupPermissionsRemoveForm($group)))
            ->onCancel(fn(Player $player) => $player->sendForm(self::editGroupForm($group)))
            ->build();
    }

    public static function editGroupPermissionsGrantForm(Group $group): CustomForm {
        $builder = CustomFormBuilder::create()
            ->title("§aGrant Permissions");

        for ($i = 0; $i < 20; $i++) {
            $builder->input("perm_" . $i, "§7Permission " . ($i + 1));
        }

        $builder->onSubmit(function (Player $player, CustomFormResponse $response) use($group): void {
            $currentGrantedPerms = $group->getGrantedPermissions();
            $currentRevokedPerms = $group->getRevokedPermissions();
            $count = 0;

            for ($i = 0; $i < 20; $i++) {
                $iPermission = trim($response->getString("perm_" . $i));
                if ($iPermission == "") continue;
                [$permission] = Utils::parsePermissionString($iPermission);

                if ($permission !== "") {
                    $count++;
                    if (in_array($permission, $currentRevokedPerms)) {
                        unset($currentRevokedPerms[array_search($permission, $currentRevokedPerms)]);
                    }

                    $currentGrantedPerms[] = $permission;
                }
            }

            $finalPerms = array_unique(array_merge($currentGrantedPerms, array_map(fn(string $revPerm) => $revPerm . "#false", $currentRevokedPerms)));

            GroupManager::getInstance()->editGroup(
                $group,
                $group->getNameTag(),
                $group->getDisplayName(),
                $group->getChatFormat(),
                $group->getColorCode(),
                $finalPerms
            );

            $player->sendForm(self::editGroupForm($group, "§7Granted §e" . $count . " permissions §7to this group."));
        });

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::editGroupPermissionsForm($group)));

        return $builder->build();
    }

    public static function editGroupPermissionsRevokeForm(Group $group): CustomForm {
        $builder = CustomFormBuilder::create()
            ->title("§cRevoke Permissions");

        for ($i = 0; $i < 20; $i++) {
            $builder->input("perm_" . $i, "§7Permission " . ($i + 1));
        }

        $builder->onSubmit(function (Player $player, CustomFormResponse $response) use($group): void {
            $currentGrantedPerms = $group->getGrantedPermissions();
            $currentRevokedPerms = $group->getRevokedPermissions();
            $count = 0;

            for ($i = 0; $i < 20; $i++) {
                $iPermission = trim($response->getString("perm_" . $i));
                if ($iPermission == "") continue;
                [$permission] = Utils::parsePermissionString($iPermission);

                if ($permission !== "") {
                    $count++;
                    if (in_array($permission, $currentGrantedPerms)) {
                        unset($currentGrantedPerms[array_search($permission, $currentGrantedPerms)]);
                    }

                    $currentRevokedPerms[] = $permission;
                }
            }

            $finalPerms = array_unique(array_merge($currentGrantedPerms, array_map(fn(string $revPerm) => $revPerm . "#false", $currentRevokedPerms)));

            GroupManager::getInstance()->editGroup(
                $group,
                $group->getNameTag(),
                $group->getDisplayName(),
                $group->getChatFormat(),
                $group->getColorCode(),
                $finalPerms
            );

            $player->sendForm(self::editGroupForm($group, "§7Revoked §e" . $count . " permissions §7from this group."));
        });

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::editGroupPermissionsForm($group)));

        return $builder->build();
    }

    public static function editGroupPermissionsRemoveForm(Group $group): CustomForm {
        $builder = CustomFormBuilder::create()
            ->useRealValues()
            ->title("§4Remove Permissions")
            ->label("§7This removes either an granted or revoked permission completely from this group's permission list.");

        for ($i = 0; $i < 20; $i++) {
            $builder->dropdown("perm_" . $i, "§7Permission " . ($i + 1));
        }

        $builder->onSubmit(function (Player $player, CustomFormResponse $response) use($group): void {
            $currentGrantedPerms = $group->getGrantedPermissions();
            $currentRevokedPerms = $group->getRevokedPermissions();
            $count = 0;

            for ($i = 0; $i < 20; $i++) {
                $iPermission = trim($response->getString("perm_" . $i));
                if ($iPermission == "") continue;
                [$permission] = Utils::parsePermissionString($iPermission);

                if ($permission !== "") {
                    $count++;
                    if (in_array($permission, $currentGrantedPerms)) {
                        unset($currentGrantedPerms[array_search($permission, $currentGrantedPerms)]);
                    } else if (in_array($permission, $currentRevokedPerms)) {
                        unset($currentRevokedPerms[array_search($permission, $currentRevokedPerms)]);
                    }
                }
            }

            $finalPerms = array_unique(array_merge($currentGrantedPerms, $currentRevokedPerms));

            GroupManager::getInstance()->editGroup(
                $group,
                $group->getNameTag(),
                $group->getDisplayName(),
                $group->getChatFormat(),
                $group->getColorCode(),
                $finalPerms
            );

            $player->sendForm(self::editGroupForm($group, "§7Removed §e" . $count . " permissions §7from this group."));
        });

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::editGroupPermissionsForm($group)));

        return $builder->build();
    }

    public static function removeGroupForm(): MenuForm {
        $builder = MenuFormBuilder::create()
            ->title("§cRemove Group")
            ->body("§7Tip: §cYou can't remove the default group.");

        foreach (GroupManager::getInstance()->getGroups() as $group) {
            if (!$group->isDefault()) {
                $builder->button($group->getFancyName(), clickClosure: fn(Player $player) => $player->sendForm(self::removeGroupConfirmationForm($group)));
            }
        }

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::mainForm()));

        return $builder->build();
    }

    public static function removeGroupConfirmationForm(Group $group): ModalForm {
        return ModalFormBuilder::create()
            ->title("§aConfirmation")
            ->body("§7Are you sure you want to remove the group §e" . $group->getFancyName() . "§8?")
            ->onSubmit(function (Player $player, bool $choice) use($group): void {
                if ($choice) {
                    GroupManager::getInstance()->removeGroup($group);
                    $player->sendForm(self::mainForm("§7Removed the group. §8(§e" . $group->getFancyName() . "§8)"));
                }
            })
            ->onCancel(fn(Player $player) => $player->sendForm(self::removeGroupForm()))
            ->build();
    }

    public static function seeGroupsForm(): MenuForm {
        $builder = MenuFormBuilder::create()
            ->title("§6See Groups");

        foreach (GroupManager::getInstance()->getGroups() as $group) {
            $builder->button($group->getFancyName(), clickClosure: fn(Player $player) => $player->sendForm(self::editGroupForm($group)));
        }

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::mainForm()));

        return $builder->build();
    }
}