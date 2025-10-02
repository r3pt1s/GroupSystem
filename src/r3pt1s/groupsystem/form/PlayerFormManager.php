<?php

namespace r3pt1s\groupsystem\form;

use DateTime;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use r3pt1s\forms\builder\CustomFormBuilder;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\menu\MenuForm;
use r3pt1s\forms\type\misc\CustomFormResponse;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\player\perm\PlayerPermission;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\util\Message;
use r3pt1s\groupsystem\util\Utils;

final class PlayerFormManager {

    public static function choosePlayerForm(): CustomForm {
        return CustomFormBuilder::create()
            ->useRealValues()
            ->title("§cManage Players")
            ->input("playerInput", "§7Name of the player", "r3pt1s")
            ->divider()
            ->dropdown("playerSelection", "§7Select the player", array_map(fn(Player $player) => $player->getName(), array_values(Server::getInstance()->getOnlinePlayers())))
            ->onCancel(fn(Player $player) => $player->sendForm(GroupFormManager::mainForm()))
            ->onSubmit(function (Player $player, CustomFormResponse $response): void {
                $playerInput = trim($response->getstring("playerInput"));
                $playerSelectionChoice = $response->getString("playerSelection");
                if ($playerInput == "") $finalPlayer = $playerSelectionChoice;
                else $finalPlayer = $playerInput;

                GroupSystem::getInstance()->getProvider()->checkPlayer($finalPlayer)->onCompletion(
                    function (bool $exists) use ($finalPlayer, $player): void {
                        if ($exists) {
                            $player->sendTip("§aLoading§8...");
                            Session::get($finalPlayer)->onLoad(function() use($finalPlayer, $player): void {
                                $player->sendForm(self::playerViewMainForm($finalPlayer));
                            });
                        }
                    },
                    fn() => $player->sendForm(GroupFormManager::mainForm(Message::PLAYER_NOT_FOUND()->parse([$finalPlayer, $playerSelectionChoice])))
                );
            })
            ->build();
    }

    public static function playerViewMainForm(string $username, string $message = ""): MenuForm {
        $session = Session::get($username);
        [$group, $actualGroup, $groups, $permissions] = [$session->getGroup(), $session->getGroup()->getGroup(), $session->getGroups(), $session->getPermissions()];

        $expireString = (string) Message::RAW_NEVER();
        if ($group->getExpireDate() instanceof DateTime) $expireString = Utils::diffString(new DateTime(), $group->getExpireDate());

        $finalContent = ($message == "" ? "" : $message . "\n\n");
        $finalContent .= "§7Group: §e" . $actualGroup->getFancyName() . "\n";
        $finalContent .= "§7Expires in: §e" . $expireString . "\n";
        $finalContent .= "§7Remaining Groups: §e" . (count($groups) == 0 ? "§cNone" : "\n§8- §e" . implode("\n§8- §e", array_map(fn(PlayerRemainingGroup $remainingGroup) => $remainingGroup->getGroup()->getFancyName() . " §8(§c" . ($remainingGroup->getTime() ?? Message::RAW_NEVER()) . "§8)", $groups))) . "\n";
        if (count($groups) > 0) {
            $finalContent .= "§7Next Highest Group: §e" . $session->getNextHighestGroup()->getGroup()->getFancyName() . "\n";
            $finalContent .= "§7Next Highest Group Duration: §e" . ($session->getNextHighestGroup()?->getTime() ?? (string) Message::RAW_NEVER()) . "\n";
        }
        $finalContent .= "§7Total Permission Entries: §e" . count($permissions) . " entries";

        return MenuFormBuilder::create()
            ->title("§e" . $username)
            ->body($finalContent)
            ->button(Message::MANAGE_PLAYERS_UI_ADD_GROUP(), clickClosure: fn(Player $player) => $player->sendForm(self::playerAddGroupForm($username)))
            ->button(Message::MANAGE_PLAYERS_UI_REMOVE_GROUP(), clickClosure: function (Player $player) use($username, $groups): void {
                if (count($groups) == 0) {
                    $player->sendForm(self::playerViewMainForm($username, Message::PLAYER_HAS_NO_GROUPS()->parse([$username])));
                } else {
                    $player->sendForm(self::playerRemoveGroupForm($username, $groups));
                }
            })
            ->button(Message::MANAGE_PLAYERS_UI_SKIP_GROUP(), clickClosure: function (Player $player) use($username, $groups): void {
                Session::get($username)->onLoad(function() use($player, $username): void {
                    if (Session::get($username)->nextGroup()) {
                        $player->sendForm(self::playerViewMainForm($username, Message::GROUP_SKIPPED()->parse([$username])));
                    } else {
                        $player->sendForm(self::playerViewMainForm($username, "Failed to skip the current group of §e" . $username . "§8."));
                    }
                });
            })
            ->button(Message::MANAGE_PLAYERS_UI_VIEW_PERMISSIONS(), clickClosure: fn(Player $player) => $player->sendForm(self::viewPlayerPermissionsForm($username, $permissions)))
            ->button(Message::MANAGE_PLAYERS_UI_BACK(), clickClosure: fn(Player $player) => $player->sendForm(GroupFormManager::mainForm()))
            ->build();
    }

    public static function playerAddGroupForm(string $username): CustomForm {
        return CustomFormBuilder::create()
            ->useRealValues()
            ->title(Message::MANAGE_PLAYERS_UI_ADD_GROUP())
            ->dropdown("group", Message::ADD_GROUP_UI_CHOOSE_GROUP(), array_map(fn(Group $group) => $group->getFancyName(), array_values(GroupManager::getInstance()->getGroups())))
            ->input("duration", Message::ADD_GROUP_UI_CHOOSE_TIME(), "1y1m1w1d1h1M1s")
            ->onSubmit(function (Player $player, CustomFormResponse $response) use ($username): void {
                $group = GroupManager::getInstance()->getGroup($groupName = TextFormat::clean($response->getString("group")));
                $duration = Utils::convertStringToDateFormat($rawDuration = $response->getString("duration")) !== null ? $rawDuration : null;
                if ($group === null) {
                    $player->sendForm(self::playerViewMainForm($username, Message::GROUP_DOESNT_EXISTS()->parse([$groupName])));
                    return;
                }

                Session::get($username)->addGroup(new PlayerRemainingGroup($group, $duration), function(bool $success) use($username, $group, $player): void {
                    if ($success) {
                        $player->sendForm(self::playerViewMainForm($username, Message::GROUP_ADDED()->parse([$group->getFancyName(), $username])));
                    } else {
                        $player->sendForm(self::playerViewMainForm($username, Message::GROUP_CANT_ADDED()->parse([$group->getFancyName(), $username])));
                    }
                });
            })
            ->onCancel(fn(Player $player) => $player->sendForm(self::playerViewMainForm($username)))
            ->build();
    }

    public static function playerRemoveGroupForm(string $username, array $groups): CustomForm {
        return CustomFormBuilder::create()
            ->useRealValues()
            ->title(Message::REMOVE_GROUP_UI_TITLE())
            ->dropdown("group", Message::REMOVE_GROUP_UI_CHOOSE_GROUP(), array_map(fn(PlayerRemainingGroup $group) => $group->getGroup()->getFancyName(), array_values($groups)))
            ->onSubmit(function (Player $player, CustomFormResponse $response) use ($username): void {
                $group = GroupManager::getInstance()->getGroup($groupName = TextFormat::clean($response->getString("group")));
                if ($group === null) {
                    $player->sendForm(self::playerViewMainForm($username, Message::GROUP_DOESNT_EXISTS()->parse([$groupName])));
                    return;
                }

                Session::get($username)->removeGroup($group, function(bool $success) use($username, $group, $player): void {
                    if ($success) {
                        $player->sendForm(self::playerViewMainForm($username, Message::GROUP_REMOVED()->parse([$group->getFancyName(), $username])));
                    } else {
                        $player->sendForm(self::playerViewMainForm($username, Message::GROUP_CANT_REMOVED()->parse([$group->getFancyName(), $username])));
                    }
                });
            })
            ->onCancel(fn(Player $player) => $player->sendForm(self::playerViewMainForm($username)))
            ->build();
    }

    public static function viewPlayerPermissionsForm(string $username, array $permissions): MenuForm {
        $builder = MenuFormBuilder::create()
            ->title("§eView permissions")
            ->button("§aGrant", clickClosure: fn(Player $player) => $player->sendForm(self::editPlayerPermissionsGrantForm($username)))
            ->button("§cRevoke", clickClosure: fn(Player $player) => $player->sendForm(self::editPlayerPermissionsRevokeForm($username)))
            ->button("§4Remove", clickClosure: fn(Player $player) => $player->sendForm(self::editPlayerPermissionsRemoveForm($username, $permissions)))
            ->divider();

        if (count($permissions) == 0) {
            $builder->button("§cNo Extra Permissions");
        } else {
            usort($permissions, fn(PlayerPermission $a, PlayerPermission $b) => $a->isGranted() <=> $b->isRevoked());
            /** @var array<PlayerPermission> $permissions */
            foreach ($permissions as $permission) {
                $builder->button("§" . ($permission->isGranted() ? "a" : "c") . $permission->getPermission() . "\n§e" . ($permission->getExpireDate() === null ? (string) Message::RAW_NEVER() : Utils::diffString(new DateTime(), $permission->getExpireDate(), false, 2)), clickClosure: fn(Player $player) => $player->sendForm(self::editSpecificPermission($username, $permission)));
            }
        }

        return $builder->build();
    }

    public static function editPlayerPermissionsGrantForm(string $username): CustomForm {
        $builder = CustomFormBuilder::create()
            ->title("§aGrant Permissions");

        for ($i = 0; $i < 20; $i++) {
            $builder->input("perm_" . $i, "§7Permission " . ($i + 1));
            $builder->input("perm_duration_" . $i, "§7Permission Duration " . ($i + 1), "1y1m1w1d1h1M1s");
            if ($i < 19) $builder->divider();
        }

        $builder->onSubmit(function (Player $player, CustomFormResponse $response) use($username): void {
            /** @var array<PlayerPermission> $permissions */
            ($session = Session::get($username))->onLoad(function () use($username, $player, $response, $session): void {
                $count = 0;
                $permissionsToGrant = [];

                for ($i = 0; $i < 20; $i++) {
                    $iPermission = trim($response->getString("perm_" . $i));
                    $iPermissionDuration = trim($response->getString("perm_duration_" . $i));
                    if ($iPermission == "") continue;
                    $iPermissionExpireDate = Utils::convertStringToDateFormat($iPermissionDuration);

                    [$permission, $expireDate] = Utils::parsePermissionString($iPermission . ($iPermissionExpireDate !== null ? "#" . $iPermissionExpireDate->format("Y-m-d H:i:s") : ""));
                    if ($permission !== "") {
                        $count++;
                        $permissionsToGrant[] = new PlayerPermission($permission, $expireDate, true);
                    }
                }

                $failedPerms = [];
                foreach ($session->updatePermission(...$permissionsToGrant) as [$i, $status]) {
                    if (!$status) $failedPerms[] = $permissionsToGrant[$i]->getPermission();
                }

                $player->sendForm(self::playerViewMainForm($username, "§7Granted §e" . ($count - count($failedPerms)) . " permissions §7to this player." . (count($failedPerms) > 0 ? "\n§cFailed to grant the following perms: §e" . implode("§8, §e", $failedPerms) : "")));
            });
        });

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::playerViewMainForm($username)));

        return $builder->build();
    }

    public static function editPlayerPermissionsRevokeForm(string $username): CustomForm {
        $builder = CustomFormBuilder::create()
            ->title("§cRevoke Permissions");

        for ($i = 0; $i < 20; $i++) {
            $builder->input("perm_" . $i, "§7Permission " . ($i + 1));
            $builder->input("perm_duration_" . $i, "§7Permission Revocation Duration " . ($i + 1), "1y1m1w1d1h1M1s");
            if ($i < 19) $builder->divider();
        }

        $builder->onSubmit(function (Player $player, CustomFormResponse $response) use($username): void {
            /** @var array<PlayerPermission> $permissions */
            ($session = Session::get($username))->onLoad(function () use($username, $player, $response, $session): void {
                $count = 0;
                $permissionsToRevoke = [];

                for ($i = 0; $i < 20; $i++) {
                    $iPermission = trim($response->getString("perm_" . $i));
                    $iPermissionDuration = trim($response->getString("perm_duration_" . $i));
                    if ($iPermission == "") continue;
                    $iPermissionExpireDate = Utils::convertStringToDateFormat($iPermissionDuration);

                    [$permission, $expireDate] = Utils::parsePermissionString($iPermission . ($iPermissionExpireDate !== null ? "#" . $iPermissionExpireDate->format("Y-m-d H:i:s") : ""));
                    if ($permission !== "") {
                        $count++;
                        $permissionsToRevoke[] = new PlayerPermission($permission, $expireDate, false);
                    }
                }

                $failedPerms = [];
                foreach ($session->updatePermission(...$permissionsToRevoke) as [$i, $status]) {
                    if (!$status) $failedPerms[] = $permissionsToRevoke[$i]->getPermission();
                }

                $player->sendForm(self::playerViewMainForm($username, "§7Revoked §e" . ($count - count($failedPerms)) . " permissions §7for this player." . (count($failedPerms) > 0 ? "\n§cFailed to revoke the following perms: §e" . implode("§8, §e", $failedPerms) : "")));
            });
        });

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::playerViewMainForm($username)));

        return $builder->build();
    }

    public static function editPlayerPermissionsRemoveForm(string $username, array $permissions): CustomForm {
        $builder = CustomFormBuilder::create()
            ->useRealValues()
            ->title("§4Remove Permissions")
            ->label("§7This removes either an granted or revoked permission completely from this group's permission list.");

        $permissions = array_map(fn(PlayerPermission $permission) => $permission->getPermission(), $permissions);
        for ($i = 0; $i < 20; $i++) {
            if (isset($permissions[$i])) {
                $builder->dropdown("perm_" . $i, "§7Permission " . ($i + 1), $permissions, $i);
            } else $builder->input("perm_" . $i, "§7Permission " . ($i + 1));
        }

        $builder->onSubmit(function (Player $player, CustomFormResponse $response) use($username): void {
            /** @var array<PlayerPermission> $permissions */
            ($session = Session::get($username))->onLoad(function () use($username, $player, $response, $session): void {
                $count = 0;
                $permissionsToRemove = [];

                for ($i = 0; $i < 20; $i++) {
                    $iPermission = trim($response->getString("perm_" . $i));
                    if ($iPermission == "") continue;

                    [$permission] = Utils::parsePermissionString($iPermission);
                    if ($permission !== "") {
                        $count++;
                        $permissionsToRemove[] = $permission;
                    }
                }

                $failedPerms = [];
                foreach ($session->removePermission(...$permissionsToRemove) as [$i, $status]) {
                    if (!$status) $failedPerms[] = $permissionsToRemove[$i];
                }

                $player->sendForm(self::playerViewMainForm($username, "§7Removed §e" . ($count - count($failedPerms)) . " permissions §7from this player." . (count($failedPerms) > 0 ? "\n§cFailed to remove the following perms: §e" . implode("§8, §e", $failedPerms) : "")));
            });
        });

        $builder->onCancel(fn(Player $player) => $player->sendForm(self::playerViewMainForm($username)));

        return $builder->build();
    }

    public static function editSpecificPermission(string $username, PlayerPermission $permission): MenuForm {
        $expireString = (string) Message::RAW_NEVER();
        if ($permission->getExpireDate() instanceof DateTime) $expireString = Utils::diffString(new DateTime(), $permission->getExpireDate());

        $content = "§7Permission: §e" . $permission->getPermission() . "\n";
        $content .= "§7Expires in: §e" . $expireString;
        return MenuFormBuilder::create()
            ->title("§c" . $permission->getPermission())
            ->body($content)
            ->button($permission->isGranted() ? "§cRevoke" : "§aGrant", clickClosure: function (Player $player) use($permission, $username): void {
                ($session = Session::get($username))->onLoad(function () use($username, $player, $permission, $session): void {
                    [, $status] = current($session->updatePermission($permission->reverseGranting()));
                    $player->sendForm(self::playerViewMainForm($username, ($status ? "§7" . ($permission->isGranted() ? "Revoked the permission from" : "Granted the permission for") . " this player." : "§cFailed to update the permission for this player.")));
                });
            })
            ->button("§4Remove", clickClosure: function (Player $player) use($permission, $username): void {
                ($session = Session::get($username))->onLoad(function () use($username, $player, $permission, $session): void {
                    [, $status] = current($session->removePermission($permission));
                    $player->sendForm(self::playerViewMainForm($username, ($status ? "§7Removed the permission from this player." : "§cFailed to update the permission for this player.")));
                });
            })
            ->build();
    }
}