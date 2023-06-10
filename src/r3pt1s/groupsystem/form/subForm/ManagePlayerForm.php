<?php

namespace r3pt1s\groupsystem\form\subForm;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use r3pt1s\groupsystem\form\MainForm;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\util\Utils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ManagePlayerForm extends MenuForm {

    private array $options = [];

    public function __construct(string $username, private PlayerGroup $currentGroup, string $message = "") {
        $expireString = Utils::parse("raw_never");
        if ($this->currentGroup->getExpireDate() instanceof \DateTime) $expireString = Utils::diffString(new \DateTime("now"), $this->currentGroup->getExpireDate());

        $message = $message . ($message == "" ? "" : "\n\n") . Utils::parse("manage_players_ui_text", [
            $this->currentGroup->getGroup()->getColorCode() . $this->currentGroup->getGroup()->getName(),
            $expireString
        ]);
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_add_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_remove_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_skip_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_see_groups"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_add_permission"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_remove_permission"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_see_permissions"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_back"));

        parent::__construct(Utils::parse("manage_players_ui_title", [$username]), $message, $this->options, function(Player $player, int $data) use($username): void {
            if ($data == 0) {
                $groups = array_values(array_map(function(Group $group): string {return $group->getColorCode() . $group->getName(); }, GroupManager::getInstance()->getGroups()));
                $player->sendForm(new CustomForm(
                    Utils::parse("add_group_ui_title"),
                    [
                        new Dropdown("group", Utils::parse("add_group_ui_choose_group"), $groups),
                        new Input("time", Utils::parse("add_group_ui_choose_time"), "1y1m1w1d12h30M30s")
                    ],
                    function(Player $player, CustomFormResponse $response) use($username, $groups): void {
                        $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")], true));
                        $time = null;
                        if (Utils::convertStringToDateFormat($response->getString("time")) !== null) $time = $response->getString("time");
                        if ($group !== null) {
                            Session::get($username)->addGroup(new PlayerRemainingGroup($group, $time), function(bool $success) use($username, $group, $player): void {
                                if ($success) {
                                    $player->sendForm(new self($username, $this->currentGroup, Utils::parse("group_added", [$group->getColorCode() . $group->getName(), $username])));
                                } else {
                                    $player->sendForm(new self($username, $this->currentGroup, Utils::parse("group_cant_added", [$group->getColorCode() . $group->getName(), $username])));
                                }
                            });
                        } else $player->sendForm(new self($username, $this->currentGroup, Utils::parse("group_doesnt_exists", [TextFormat::clean($groups[$response->getInt("group")], true)])));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username, $this->currentGroup));
                    }
                ));
            } else if ($data == 1) {
                $nextFormHandler = function(Player $player, array $groups, string $username) {
                    if (count($groups) == 0) {
                        $player->sendForm(new self($username, $this->currentGroup, Utils::parse("player_has_no_groups", [$username])));
                    } else {
                        $player->sendForm(new CustomForm(
                            Utils::parse("remove_group_ui_title"),
                            [
                                new Dropdown("group", Utils::parse("remove_group_ui_choose_group"), $groups),
                            ],
                            function(Player $player, CustomFormResponse $response) use ($username, $groups): void {
                                $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")], true));
                                if ($group !== null) {
                                    Session::get($username)->removeGroup($group, function(bool $success) use($username, $group, $player): void {
                                        if ($success) {
                                            $player->sendForm(new self($username, $this->currentGroup, Utils::parse("group_removed", [$group->getColorCode() . $group->getName(), $username])));
                                        } else {
                                            $player->sendForm(new self($username, $this->currentGroup, Utils::parse("group_cant_removed", [$group->getColorCode() . $group->getName(), $username])));
                                        }
                                    });
                                } else $player->sendForm(new self($username, $this->currentGroup, Utils::parse("group_doesnt_exists")));
                            }, function(Player $player) use ($username): void {
                            $player->sendForm(new self($username, $this->currentGroup));
                        }
                        ));
                    }
                };

                if (Session::get($username)->isLoaded()) {
                    $groups = array_map(fn(PlayerRemainingGroup $group) => $group->getGroup()->getColorCode() . $group->getGroup()->getName(), Session::get($username)->getGroups());
                    $nextFormHandler($player, array_values($groups), $username);
                } else {
                    Session::get($username)->onLoad(function(PlayerGroup $group, array $groups) use($username, $player, $nextFormHandler): void {
                        $nextFormHandler($player, array_values($groups), $username);
                    });
                }
            } else if ($data == 2) {
                $nextFormHandler = function(Player $player, PlayerGroup $group, string $username): void {
                    $player->sendForm(new self($username, $group, Utils::parse("group_skipped", [$username])));
                };

                if (Session::get($username)->isLoaded()) {
                    Session::get($username)->nextGroup();
                    $nextFormHandler($player, Session::get($username)->getGroup(), $username);
                } else {
                    Session::get($username)->onLoad(function() use($player, $username, $nextFormHandler): void {
                        Session::get($username)->nextGroup();
                        $nextFormHandler($player, Session::get($username)->getGroup(), $username);
                    });
                }
            } else if ($data == 3) {
                try {
                    $nextFormHandler = function(Player $player, array $groups, string $username) {
                        $player->sendForm(new MenuForm(
                            Utils::parse("see_groups_ui_title"),
                            Utils::parse("see_groups_ui_text", [$username, count($groups)]),
                            array_map(fn(PlayerRemainingGroup $group) => new MenuOption($group->getGroup()->getColorCode() . $group->getGroup()->getName() . "\n§e" . ($group->getTime() === null ? Utils::parse("raw_never") : "§e" . $group->getTime())), $groups),
                            function(Player $player, int $data) use($username): void {
                                $player->sendForm(new self($username, $this->currentGroup));
                            }, function(Player $player) use($username): void {
                            $player->sendForm(new self($username, $this->currentGroup));
                        }
                        ));
                    };

                    if (Session::get($username)->isLoaded()) {
                        $nextFormHandler($player, Session::get($username)->getGroups(), $username);
                    } else {
                        Session::get($username)->onLoad(function(PlayerGroup $group, array $groups) use($username, $player, $nextFormHandler): void {
                            $nextFormHandler($player, $groups, $username);
                        });
                    }
                } catch (\Exception $exception) {
                    \GlobalLogger::get()->logException($exception);
                }
            } else if ($data == 4) {
                $player->sendForm(new CustomForm(
                    Utils::parse("add_permission_ui_title"),
                    [new Input("permission", Utils::parse("add_permission_ui_which_permission"))],
                    function(Player $player, CustomFormResponse $response) use($username): void {
                        $permission = $response->getString("permission");
                        if ($permission !== "") {
                            if (Session::get($username)->isLoaded()) {
                                Session::get($username)->onLoad(fn(PlayerGroup $group, array $groups, array $permissions) => Session::get($username)->addPermission($permission));
                            } else {
                                Session::get($username)->addPermission($permission);
                            }

                            $player->sendForm(new self($username, $this->currentGroup, Utils::parse("permission_added", [$username, $permission])));
                        } else $player->sendForm(new self($username, $this->currentGroup, Utils::parse("provide_permission")));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username, $this->currentGroup));
                    }
                ));
            } else if ($data == 5) {
                $player->sendForm(new CustomForm(
                    Utils::parse("remove_permission_ui_title"),
                    [new Input("permission", Utils::parse("remove_permission_ui_which_permission"))],
                    function(Player $player, CustomFormResponse $response) use($username): void {
                        $permission = $response->getString("permission");
                        if ($permission !== "") {
                            if (Session::get($username)->isLoaded()) {
                                Session::get($username)->onLoad(fn(PlayerGroup $group, array $groups, array $permissions) => Session::get($username)->removePermission($permission));
                            } else {
                                Session::get($username)->removePermission($permission);
                            }

                            $player->sendForm(new self($username, $this->currentGroup, Utils::parse("permission_removed", [$username, $permission])));
                        } else $player->sendForm(new self($username, $this->currentGroup, Utils::parse("provide_permission")));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username, $this->currentGroup));
                    }
                ));
            } else if ($data == 6) {
                $nextFormHandler = function(Player $player, array $permissions, string $username) {
                    $player->sendForm(new MenuForm(
                        Utils::parse("see_permissions_ui_title"),
                        Utils::parse("see_permissions_ui_text", [$username, count($permissions)]),
                        array_map(fn(string $permission) => new MenuOption("§e" . $permission), $permissions),
                        function(Player $player, int $data) use($username): void {
                            $player->sendForm(new self($username, $this->currentGroup));
                        }, function(Player $player) use($username): void {
                            $player->sendForm(new self($username, $this->currentGroup));
                        }
                    ));
                };

                if (Session::get($username)->isLoaded()) {
                    $nextFormHandler($player, Session::get($username)->getPermissions(), $username);
                } else {
                    Session::get($username)->onLoad(fn(PlayerGroup $group, array $groups, array $permissions) => $nextFormHandler($player, $permissions, $username));
                }
            } else {
                $player->sendForm(new MainForm());
            }
        }, function(Player $player): void {
            $player->sendForm(new MainForm());
        });
    }
}