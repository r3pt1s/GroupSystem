<?php

namespace r3pt1s\groupsystem\util;

use pocketmine\utils\RegistryTrait;
use r3pt1s\groupsystem\GroupSystem;

/**
 * @method static Message PREFIX()
 * @method static Message RAW_YEAR()
 * @method static Message RAW_MONTH()
 * @method static Message RAW_DAY()
 * @method static Message RAW_HOUR()
 * @method static Message RAW_MINUTE()
 * @method static Message RAW_SECOND()
 * @method static Message RAW_NEVER()
 * @method static Message UPDATER_OUTDATED()
 * @method static Message UPDATER_OUTDATED_2()
 * @method static Message UPDATER_ERROR()
 * @method static Message UPDATER_UP_TO_DATE()
 * @method static Message NO_PERM()
 * @method static Message COMMAND_DESCRIPTION_GROUP()
 * @method static Message GROUP_CHANGED()
 * @method static Message PLAYER_NOT_FOUND()
 * @method static Message GROUP_ADDED()
 * @method static Message GROUP_CANT_ADDED()
 * @method static Message GROUP_REMOVED()
 * @method static Message GROUP_CANT_REMOVED()
 * @method static Message GROUP_CREATED()
 * @method static Message GROUP_DELETED()
 * @method static Message GROUP_EDITED()
 * @method static Message GROUP_INFO()
 * @method static Message GROUP_DOESNT_EXISTS()
 * @method static Message GROUP_ALREADY_EXISTS()
 * @method static Message GROUPS_RELOADED()
 * @method static Message PERMISSION_ADDED()
 * @method static Message PERMISSION_REMOVED()
 * @method static Message PROVIDE_PERMISSION()
 * @method static Message PROVIDE_GROUP_NAME()
 * @method static Message PLAYER_HAS_NO_GROUPS()
 * @method static Message GROUP_SKIPPED()
 * @method static Message MAIN_UI_TITLE()
 * @method static Message MAIN_UI_TEXT()
 * @method static Message MAIN_UI_MANAGE_PLAYERS()
 * @method static Message MAIN_UI_MANAGE_GROUPS()
 * @method static Message SELECT_PLAYER_UI_TITLE()
 * @method static Message SELECT_PLAYER_INPUT_TEXT()
 * @method static Message MANAGE_PLAYERS_UI_TITLE()
 * @method static Message MANAGE_PLAYERS_UI_TEXT()
 * @method static Message MANAGE_PLAYERS_UI_ADD_GROUP()
 * @method static Message MANAGE_PLAYERS_UI_REMOVE_GROUP()
 * @method static Message MANAGE_PLAYERS_UI_SKIP_GROUP()
 * @method static Message MANAGE_PLAYERS_UI_SEE_GROUPS()
 * @method static Message MANAGE_PLAYERS_UI_UPDATE_PERMISSION()
 * @method static Message MANAGE_PLAYERS_UI_REMOVE_PERMISSION()
 * @method static Message MANAGE_PLAYERS_UI_VIEW_PERMISSIONS()
 * @method static Message MANAGE_PLAYERS_UI_BACK()
 * @method static Message ADD_GROUP_UI_TITLE()
 * @method static Message ADD_GROUP_UI_CHOOSE_GROUP()
 * @method static Message ADD_GROUP_UI_CHOOSE_TIME()
 * @method static Message REMOVE_GROUP_UI_TITLE()
 * @method static Message REMOVE_GROUP_UI_CHOOSE_GROUP()
 * @method static Message SEE_GROUPS_UI_TITLE()
 * @method static Message SEE_GROUPS_UI_TEXT()
 * @method static Message ADD_PERMISSION_UI_TITLE()
 * @method static Message ADD_PERMISSION_UI_WHICH_PERMISSION()
 * @method static Message ADD_PERMISSION_UI_CHOOSE_TIME()
 * @method static Message REMOVE_PERMISSION_UI_TITLE()
 * @method static Message REMOVE_PERMISSION_UI_WHICH_PERMISSION()
 * @method static Message SEE_PERMISSIONS_UI_TITLE()
 * @method static Message SEE_PERMISSIONS_UI_TEXT()
 * @method static Message MANAGE_GROUPS_UI_TITLE()
 * @method static Message MANAGE_GROUPS_UI_CREATE_GROUP()
 * @method static Message MANAGE_GROUPS_UI_REMOVE_GROUP()
 * @method static Message MANAGE_GROUPS_UI_EDIT_GROUP()
 * @method static Message MANAGE_GROUPS_UI_SEE_GROUPS()
 * @method static Message MANAGE_GROUPS_UI_RELOAD_GROUPS()
 * @method static Message MANAGE_GROUPS_UI_BACK()
 * @method static Message CREATE_GROUP_UI_TITLE()
 * @method static Message CREATE_GROUP_UI_TEXT()
 * @method static Message CREATE_GROUP_UI_NAME()
 * @method static Message CREATE_GROUP_UI_NAME_TAG()
 * @method static Message CREATE_GROUP_UI_DISPLAYNAME()
 * @method static Message CREATE_GROUP_UI_COLOR_CODE()
 * @method static Message CREATE_GROUP_UI_CHATFORMAT()
 * @method static Message CREATE_GROUP_UI_PERMISSIONS()
 * @method static Message CREATE_GROUP_UI_PERMISSIONS_TIP()
 * @method static Message DELETE_GROUP_UI_TITLE()
 * @method static Message DELETE_GROUP_UI_CHOOSE_GROUP()
 * @method static Message EDIT_GROUP_CHOOSE_UI_TITLE()
 * @method static Message EDIT_GROUP_UI_TEXT()
 * @method static Message EDIT_GROUP_UI_CHOOSE_GROUP()
 * @method static Message EDIT_GROUP_UI_NAME_TAG()
 * @method static Message EDIT_GROUP_UI_DISPLAYNAME()
 * @method static Message EDIT_GROUP_UI_COLOR_CODE()
 * @method static Message EDIT_GROUP_UI_CHATFORMAT()
 * @method static Message EDIT_GROUP_UI_PERMISSIONS()
 * @method static Message SEE_AVAILABLE_GROUPS_UI_TITLE()
 * @method static Message SEE_AVAILABLE_GROUPS_UI_TEXT()
 * @method static Message SEE_AVAILABLE_GROUP_TITLE()
 * @method static Message SEE_AVAILABLE_GROUP_TEXT()
 * @method static Message SEE_AVAILABLE_GROUP_BACK()
 */
final class Message {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("prefix", new self("prefix"));
        self::_registryRegister("raw_year", new self("raw_year"));
        self::_registryRegister("raw_month", new self("raw_month"));
        self::_registryRegister("raw_day", new self("raw_day"));
        self::_registryRegister("raw_hour", new self("raw_hour"));
        self::_registryRegister("raw_minute", new self("raw_minute"));
        self::_registryRegister("raw_second", new self("raw_second"));
        self::_registryRegister("raw_never", new self("raw_never"));
        self::_registryRegister("updater_outdated", new self("updater_outdated"));
        self::_registryRegister("updater_outdated_2", new self("updater_outdated_2"));
        self::_registryRegister("updater_error", new self("updater_error"));
        self::_registryRegister("updater_up_to_date", new self("updater_uptodate"));
        self::_registryRegister("no_perm", new self("no_perm"));
        self::_registryRegister("command_description_group", new self("command_description_group"));
        self::_registryRegister("group_changed", new self("group_changed"));
        self::_registryRegister("player_not_found", new self("player_not_found"));
        self::_registryRegister("group_added", new self("group_added"));
        self::_registryRegister("group_cant_added", new self("group_cant_added"));
        self::_registryRegister("group_removed", new self("group_removed"));
        self::_registryRegister("group_cant_removed", new self("group_cant_removed"));
        self::_registryRegister("group_created", new self("group_created"));
        self::_registryRegister("group_deleted", new self("group_deleted"));
        self::_registryRegister("group_edited", new self("group_edited"));
        self::_registryRegister("group_info", new self("group_info"));
        self::_registryRegister("group_doesnt_exists", new self("group_doesnt_exists"));
        self::_registryRegister("group_already_exists", new self("group_already_exists"));
        self::_registryRegister("groups_reloaded", new self("groups_reloaded"));
        self::_registryRegister("permission_added", new self("permission_added"));
        self::_registryRegister("permission_removed", new self("permission_removed"));
        self::_registryRegister("provide_permission", new self("provide_permission"));
        self::_registryRegister("provide_group_name", new self("provide_group_name"));
        self::_registryRegister("player_has_no_groups", new self("player_has_no_groups"));
        self::_registryRegister("group_skipped", new self("group_skipped"));
        self::_registryRegister("main_ui_title", new self("main_ui_title"));
        self::_registryRegister("main_ui_text", new self("main_ui_text"));
        self::_registryRegister("main_ui_manage_players", new self("main_ui_manage_players"));
        self::_registryRegister("main_ui_manage_groups", new self("main_ui_manage_groups"));
        self::_registryRegister("select_player_ui_title", new self("select_player_ui_title"));
        self::_registryRegister("select_player_input_text", new self("select_player_input_text"));
        self::_registryRegister("manage_players_ui_title", new self("manage_players_ui_title"));
        self::_registryRegister("manage_players_ui_text", new self("manage_players_ui_text"));
        self::_registryRegister("manage_players_ui_add_group", new self("manage_players_ui_add_group"));
        self::_registryRegister("manage_players_ui_remove_group", new self("manage_players_ui_remove_group"));
        self::_registryRegister("manage_players_ui_skip_group", new self("manage_players_ui_skip_group"));
        self::_registryRegister("manage_players_ui_see_groups", new self("manage_players_ui_see_groups"));
        self::_registryRegister("manage_players_ui_update_permission", new self("manage_players_ui_update_permission"));
        self::_registryRegister("manage_players_ui_remove_permission", new self("manage_players_ui_remove_permission"));
        self::_registryRegister("manage_players_ui_view_permissions", new self("manage_players_ui_view_permissions"));
        self::_registryRegister("manage_players_ui_back", new self("manage_players_ui_back"));
        self::_registryRegister("add_group_ui_title", new self("add_group_ui_title"));
        self::_registryRegister("add_group_ui_choose_group", new self("add_group_ui_choose_group"));
        self::_registryRegister("add_group_ui_choose_time", new self("add_group_ui_choose_time"));
        self::_registryRegister("remove_group_ui_title", new self("remove_group_ui_title"));
        self::_registryRegister("remove_group_ui_choose_group", new self("remove_group_ui_choose_group"));
        self::_registryRegister("see_groups_ui_title", new self("see_groups_ui_title"));
        self::_registryRegister("see_groups_ui_text", new self("see_groups_ui_text"));
        self::_registryRegister("add_permission_ui_title", new self("add_permission_ui_title"));
        self::_registryRegister("add_permission_ui_which_permission", new self("add_permission_ui_which_permission"));
        self::_registryRegister("add_permission_ui_choose_time", new self("add_permission_ui_choose_time"));
        self::_registryRegister("remove_permission_ui_title", new self("remove_permission_ui_title"));
        self::_registryRegister("remove_permission_ui_which_permission", new self("remove_permission_ui_which_permission"));
        self::_registryRegister("see_permissions_ui_title", new self("see_permissions_ui_title"));
        self::_registryRegister("see_permissions_ui_text", new self("see_permissions_ui_text"));
        self::_registryRegister("manage_groups_ui_title", new self("manage_groups_ui_title"));
        self::_registryRegister("manage_groups_ui_create_group", new self("manage_groups_ui_create_group"));
        self::_registryRegister("manage_groups_ui_remove_group", new self("manage_groups_ui_remove_group"));
        self::_registryRegister("manage_groups_ui_edit_group", new self("manage_groups_ui_edit_group"));
        self::_registryRegister("manage_groups_ui_see_groups", new self("manage_groups_ui_see_groups"));
        self::_registryRegister("manage_groups_ui_reload_groups", new self("manage_groups_ui_reload_groups"));
        self::_registryRegister("manage_groups_ui_back", new self("manage_groups_ui_back"));
        self::_registryRegister("create_group_ui_title", new self("create_group_ui_title"));
        self::_registryRegister("create_group_ui_text", new self("create_group_ui_text"));
        self::_registryRegister("create_group_ui_name", new self("create_group_ui_name"));
        self::_registryRegister("create_group_ui_name_tag", new self("create_group_ui_nametag"));
        self::_registryRegister("create_group_ui_displayname", new self("create_group_ui_displayname"));
        self::_registryRegister("create_group_ui_color_code", new self("create_group_ui_colorcode"));
        self::_registryRegister("create_group_ui_chatformat", new self("create_group_ui_chatformat"));
        self::_registryRegister("create_group_ui_permissions", new self("create_group_ui_permissions"));
        self::_registryRegister("create_group_ui_permissions_tip", new self("create_group_ui_permissions_tip"));
        self::_registryRegister("delete_group_ui_title", new self("delete_group_ui_title"));
        self::_registryRegister("delete_group_ui_choose_group", new self("delete_group_ui_choose_group"));
        self::_registryRegister("edit_group_choose_ui_title", new self("edit_group_choose_ui_title"));
        self::_registryRegister("edit_group_ui_text", new self("edit_group_ui_text"));
        self::_registryRegister("edit_group_ui_choose_group", new self("edit_group_ui_choose_group"));
        self::_registryRegister("edit_group_ui_name_tag", new self("edit_group_ui_nametag"));
        self::_registryRegister("edit_group_ui_displayname", new self("edit_group_ui_displayname"));
        self::_registryRegister("edit_group_ui_color_code", new self("edit_group_ui_colorcode"));
        self::_registryRegister("edit_group_ui_chatformat", new self("edit_group_ui_chatformat"));
        self::_registryRegister("edit_group_ui_permissions", new self("edit_group_ui_permissions"));
        self::_registryRegister("see_available_groups_ui_title", new self("see_available_groups_ui_title"));
        self::_registryRegister("see_available_groups_ui_text", new self("see_available_groups_ui_text"));
        self::_registryRegister("see_available_group_title", new self("see_available_group_title"));
        self::_registryRegister("see_available_group_text", new self("see_available_group_text"));
        self::_registryRegister("see_available_group_back", new self("see_available_group_back"));
    }

    public function __construct(private readonly string $key) {}

    public function parse(array $parameters = []): string {
        $msgConf = GroupSystem::getInstance()->getMessageConfig();
        $result = str_replace(["{PREFIX}", "{line}"], [$msgConf->get("prefix", ""), "\n"], $msgConf->get($this->key, $this->key));
        foreach ($parameters as $index => $parameter) $result = str_replace("{%" . $index . "}", $parameter, $result);
        return $result;
    }

    public function __toString(): string {
        return $this->parse();
    }

    public function getKey(): string {
        return $this->key;
    }
}