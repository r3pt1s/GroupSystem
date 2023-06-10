<?php

namespace r3pt1s\groupsystem\form;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use r3pt1s\groupsystem\form\subForm\ManageGroupsForm;
use r3pt1s\groupsystem\form\subForm\ManagePlayerForm;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\util\Utils;
use pocketmine\player\Player;

class MainForm extends MenuForm {

    private array $options = [];

    public function __construct(string $message = "") {
        $this->options[] = new MenuOption(Utils::parse("main_ui_manage_players"));
        $this->options[] = new MenuOption(Utils::parse("main_ui_manage_groups"));

        parent::__construct(Utils::parse("main_ui_title"), $message, $this->options, function(Player $player, int $data): void {
            if ($data == 0) {
                $player->sendForm(new CustomForm(
                    Utils::parse("select_player_ui_title"),
                    [new Input("username", Utils::parse("select_player_input_text"), "", $player->getName())],
                    function(Player $player, CustomFormResponse $response): void {
                        $username = $response->getString("username");
                        GroupSystem::getInstance()->getProvider()->checkPlayer($username)->onCompletion(
                            function(bool $exists) use($username, $player): void {
                                if ($exists) {
                                    Session::get($username)->onLoad(function(PlayerGroup $group, array $groups, array $permissions) use($username, $player): void {
                                        $player->sendForm(new ManagePlayerForm($username, $group));
                                    });
                                } else $player->sendForm(new self(Utils::parse("player_not_found", [$username])));
                            },
                            function() use($username, $player): void {
                                $player->sendForm(new self(Utils::parse("player_not_found", [$username])));
                            }
                        );
                    }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                ));
            } else if ($data == 1) {
                $player->sendForm(new ManageGroupsForm());
            }
        });
    }
}