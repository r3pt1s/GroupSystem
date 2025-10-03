# [GroupSystem](https://poggit.pmmp.io/p/GroupSystem) [![](https://poggit.pmmp.io/shield.dl/GroupSystem)](https://poggit.pmmp.io/p/GroupSystem) [![](https://poggit.pmmp.io/shield.dl.total/GroupSystem)](https://poggit.pmmp.io/p/GroupSystem)

Manage permissions and ranks of the players!

## Features
- **Full-Custom** Messages
- **Temp**-Ranks
- **Temp**-Permissions (including negative permissions -> negative means a certain permission is revoked for a group or player)
- **Custom** Paths
- **ScoreHud** Support
- **Update** Checker
- **MySQL** Support

## Commands
| Usage      | Description                        | Permission                                    |
|------------|------------------------------------|-----------------------------------------------|
| /group     | Manage the groups and player       | groupsystem.group.command                     |
| /groupinfo | See your group and the expire date | groupsystem.groupinfo.command (default: true) |

## ScoreHud Tags
| Tag                      | Description                            |
|--------------------------|----------------------------------------|
| groupsystem.group        | Shows the group name with the color    |
| groupsystem.group.name   | Shows the group name without the color |
| groupsystem.group.expire | Shows the remaining time of your group |

## Configuration
```yaml
# The data provider you want to use to store and retrieve player & group data.
# mysql, json, yml
provider: json

# Your MySQL login, if provider is set to 'mysql'
mysql:
  host: "localhost"
  port: 3306
  username: "root"
  password: "your_password"
  database: "your_database"

# The default group used for every player
DefaultGroup: Player
# When enabled, the plugin checks itself for updates
Update-Check: true
# When enabled, debugs shown in the console (if debug log level > 1 in your pocketmine.yml) are also shown in-game
InGameDebugging: false
# Where your groups are saved (directory only)
# Example: /home/GroupSystem/
# Default: plugin_data/GroupSystem/
Groups-Path: default
# Where all the player data is saved (directory only)
# Example: /home/GroupSystem/players/
# Default: plugin_data/GroupSystem/players/
Players-Path: default
# Where all the group data is saved (directory only)
# Example: /home/GroupSystem/
# Default: plugin_data/GroupSystem/
Messages-Path: default
# How much time a player session has to retrieve its data before it gets kicked
# Default: 5 (in seconds)
Session-Timeout: 5
# This defines the group hierarchy.
# If the list is empty, the actual order from configs and databases is used.
# if a group was left out it gets put at the end of the hierarchy.
# Example:
# Group-Hierarchy:
#  - Owner
#  - Admin
#  - Developer
#  - Player
Group-Hierarchy: []
```

## Messages
(The latest version v3.4.0 does not use customizable messages for new messages cuz I am too lazy)
```yaml
prefix: §8» §l§cGroupSystem §r§8| §r§7
raw_year: year(s)
raw_month: month(s)
raw_day: day(s)
raw_hour: hour(s)
raw_minute: minute(s)
raw_second: second(s)
raw_never: NEVER
updater_outdated: '{PREFIX}§cYour version of §e''GroupSystem'' §coutdated!'
updater_outdated_2: '{PREFIX}§cYour Version: §e{%0} §r§8| §cLatest Version: §e{%1}'
updater_error: '{PREFIX}§cAn error has occurred! Disabling the plugin...'
updater_uptodate: '{PREFIX}§aYour version of §e''GroupSystem'' §ais up to date!'
no_perm: '{PREFIX}§cYou don''t have the permission to use this command!'
command.description.group: Manage the groups
group_changed: '{PREFIX}Your group was changed to §e{%0}§7!{line}{PREFIX}Expires in:
  §e{%1}'
player_not_found: §cThe player §e{%0} §7wasn't found!
group_added: §7The group §e{%0} §7was added to the player §e{%1}§7!
group_cant_added: §cThe group §e{%0} §ccan't added to the player §e{%1}§c!
group_removed: §7The group §e{%0} §7was removed from the player §e{%1}§7!
group_cant_removed: §cThe group §e{%0} §ccan't removed from the player §e{%1}§c!
group_created: §7The group §e{%0} §7was created!
group_deleted: §7The group §e{%0} §7was removed!
group_edited: §7The group §e{%0} §7was edited!
group_info: '{PREFIX}§7Your group: §e{%0}{line}{PREFIX}§7Expires in: §e{%1}'
group_doesnt_exists: §cThe group §e{%0} §cdoesn't exists!
group_already_exists: §cThe group §e{%0} §calready exists!
groups_reloaded: §7The groups were reloaded!
groups_reload_failed: §cFailed to reload the groups.
permission_added: §7The permission §e{%1} §7was added to the player §e{%0}§7!
permission_removed: §7The permission §e{%1} §7was removed from the player §e{%0}§7!
provide_permission: §cPlease provide a permission!
provide_group_name: §cPlease provide a group name!
player_has_no_groups: §7The player §e{%0} §7has §cno groups§7!
group_skipped: §7The group of the player §e{%0} §7was skipped!
main_ui_title: §cGroupSystem
main_ui_text: '§7There are currently §e{%0} group(s) §aavailable§8: §e{%1}'
main_ui_manage_players: §cManage Players
main_ui_manage_groups: §cManage Groups
select_player_ui_title: §7Provide a player
select_player_input_text: §7Username
manage_players_ui_title: §cManage Players §8- §e{%0}
manage_players_ui_text: '§7Group: §e{%0}{line}§7Expires in: §e{%1}'
manage_players_ui_add_group: §aAdd group
manage_players_ui_remove_group: §cRemove group
manage_players_ui_skip_group: §2Skip group
manage_players_ui_see_groups: §eSee groups
manage_players_ui_add_permission: §aAdd permission
manage_players_ui_remove_permission: §cRemove permission
manage_players_ui_see_permissions: §eSee permissions
manage_players_ui_back: §4Back
add_group_ui_title: §aAdd group
add_group_ui_choose_group: §7Choose a group
add_group_ui_choose_time: §7Choose a time §8(§cLeave it blank for lifetime§8)
remove_group_ui_title: §cRemove group
remove_group_ui_choose_group: §7Choose a group
see_groups_ui_title: §eSee groups
see_groups_ui_text: §7The player §e{%0} §7has §e{%1} groups§7!
add_permission_ui_title: §aAdd permission
add_permission_ui_which_permission: §7Which permission would you add?
add_permission_ui_choose_time: §7Choose a time §8(§cLeave it blank for lifetime§8)
remove_permission_ui_title: §cRemove permission
remove_permission_ui_which_permission: §7Which permission would you remove?
see_permissions_ui_title: §eSee permissions
see_permissions_ui_text: §7The player §e{%0} §7has §e{%1} extra permissions§7!
manage_groups_ui_title: §cManage Groups
manage_groups_ui_create_group: §aCreate group
manage_groups_ui_remove_group: §cRemove group
manage_groups_ui_edit_group: §eEdit group
manage_groups_ui_see_groups: §6See groups
manage_groups_ui_reload_groups: §3Reload groups
manage_groups_ui_back: §4Back
create_group_ui_title: §aCreate group
create_group_ui_text: §cLeave fields blank to set the value to the default!
create_group_ui_name: §7Name of the Group
create_group_ui_nametag: §7NameTag of the Group §8(§e{name} §8= §7Player§8)
create_group_ui_displayname: §7DisplayName of the Group §8(§e{name} §8= §7Player§8)
create_group_ui_colorcode: '§7ColorCode of the Group §8(§7Example: §ePARAGRAPH+4 §8=
  §4DARK_RED§8)'
create_group_ui_chatformat: §7ChatFormat of the Group §8(§e{name} §8= §7Player§8,
  §e{msg} §8= §7Message§8)
create_group_ui_permissions: §7Permissions of the Group §8(§cSeperated by §e;§8)
delete_group_ui_title: §cRemove group
delete_group_ui_choose_group: §7Choose a group
edit_group_ui_title: §eEdit group
```