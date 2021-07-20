<?php

/**
 * 
 *  BlockCommands allows setting commands for when people punch, break, step, or interact with blocks.
 *  Copyright (C) <2021>  <DiamondStrider1>
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 * 
 */

declare(strict_types=1);

namespace DiamondStrider1\BlockCommands\commands\management;

use DiamondStrider1\BlockCommands\BCPlugin;
use DiamondStrider1\BlockCommands\commands\BCCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class ManageCommand extends BCCommand
{
    public function __construct(string $name, BCPlugin $owner)
    {
        parent::__construct($name, $owner);
        $this->setDescription("Let's the player manage BlockCommands on the server.");
        $this->setPermission("blockcommands.manage");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch (array_shift($args)) {
            case "loadconfig":
                $this->loadConfig($sender);
                break;
            case "saveconfig":
                $this->saveConfig($sender);
                break;
            case "create":
                $this->create($sender, $label, $args);
                break;
            case "list":
                $this->list($sender);
                break;
            case "remove":
                $this->remove($sender, $label, $args);
                break;
            case "edit":
                $this->edit($sender, $label, $args);
                break;
            case "help":
                $editCmd = array_shift($args);
                if ($editCmd) {
                    $this->sendEditHelp($sender, $label, $editCmd);
                    break;
                }
            default:
                $this->sendHelp($sender, $label);
                break;
        }

        return true;
    }

    private function sendHelp(CommandSender $sender, string $label)
    {
        $prefix = BCCommand::PREFIX;
        /** @var BCPlugin */
        $plugin = $this->getPlugin();
        $version = $plugin->getDescription()->getVersion();
        $sender->sendMessage($prefix . "BlockCommands (v$version) `/$label` help:");

        $sender->sendMessage($prefix . "General commands " . TF::ITALIC . "(/$label <cmd>): " .
            TF::RESET . TF::YELLOW . "help, loadconfig, saveconfig, create, list, remove");

        $sender->sendMessage($prefix . "Edit commands " . TF::ITALIC . "(/$label edit <cmd>): " .
            TF::RESET . TF::YELLOW . "configure, addcommand, ");
        $sender->sendMessage($prefix . TF::YELLOW .
            "listcommands, removecommand, addblock, addarea, ");
        $sender->sendMessage($prefix . TF::YELLOW .
            "listattachments, removeattachment, clearattachments");
    }

    private function loadConfig(CommandSender $sender)
    {
        $prefix = BCCommand::PREFIX;
        /** @var BCPlugin */
        $plugin = $this->getPlugin();

        $sender->sendMessage($prefix . "Reloading Data");
        $time = microtime(true);
        $plugin->reloadData();
        $diff = microtime(true) - $time;
        $sender->sendMessage($prefix . sprintf("Data Reloaded (%.2fs)", $diff));
    }

    private function saveConfig(CommandSender $sender)
    {
        $prefix = BCCommand::PREFIX;
        /** @var BCPlugin */
        $plugin = $this->getPlugin();

        $sender->sendMessage($prefix . "Saving Data");
        $time = microtime(true);
        $plugin->saveData();
        $diff = microtime(true) - $time;
        $sender->sendMessage($prefix . sprintf("Data Saved (%.2fs)", $diff));
    }

    private function create(CommandSender $sender, string $label, array $args)
    {
        $prefix = BCCommand::PREFIX;
        $error = BCCommand::ERROR_PREFIX;
        /** @var BCPlugin */
        $plugin = $this->getPlugin();

        $id = array_shift($args);
        if (!$id) {
            $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
            return;
        }

        if (isset($plugin->getBlockCommands()[$id])) {
            $sender->sendMessage($error . "That BlockCommand already exists.");
            return;
        }

        $bc = $plugin->createBlockCommand($id);
        $events = [];

        if (count($args) > 0) {
            foreach ($args as $ev) {
                if (array_search($ev, BCPlugin::VALID_EVENTS, true) !== false) {
                    if (array_search($ev, $events, true)) continue;
                    $events[] = $ev;
                    $bc["events"][$ev] = true;
                }
            }
            $plugin->changeBlockCommand($id, $bc);
        }

        $sender->sendMessage($prefix . "Successfully created `$id` " . TF::WHITE . "(" . TF::YELLOW . implode(TF::WHITE . ", " . TF::YELLOW, $events) . TF::WHITE . ").");
        $sender->sendMessage($prefix . "Use `/$label help` for commands to customize it.");
    }

    private function list(CommandSender $sender)
    {
        $prefix = BCCommand::PREFIX;
        /** @var BCPlugin */
        $plugin = $this->getPlugin();

        $bcs = $plugin->getBlockCommands();
        $sender->sendMessage($prefix . "BlockCommands List:");
        foreach ($bcs as $id => $bc) {
            $events = [];
            foreach ($bc["events"] as $ev => $enabled) {
                if ($enabled) {
                    $events[] = $ev;
                }
            }
            $sender->sendMessage($prefix . "$id: " . TF::WHITE . "(" . TF::YELLOW .
                implode(TF::WHITE . ", " . TF::YELLOW, $events) . TF::WHITE . ")");
        }
    }

    private function remove(CommandSender $sender, string $label, array $args)
    {
        $prefix = BCCommand::PREFIX;
        $error = BCCommand::ERROR_PREFIX;
        /** @var BCPlugin */
        $plugin = $this->getPlugin();

        $id = array_shift($args);
        if (!$id) {
            $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
            return;
        }

        if (!$plugin->changeBlockCommand($id)) {
            $sender->sendMessage($error . "That BlockCommand does not exist.");
            return;
        }
        $sender->sendMessage($prefix . "Successfully removed $id.");
        $sender->sendMessage($prefix . "Use `/$label create` to make a new one.");
    }

    private function edit(CommandSender $sender, string $label, array $args)
    {
        $prefix = BCCommand::PREFIX;
        $error = BCCommand::ERROR_PREFIX;
        /** @var BCPlugin */
        $plugin = $this->getPlugin();

        $subcommand = array_shift($args);

        switch ($subcommand) {
            case "configure":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];
                $events = [];
                foreach (BCPlugin::VALID_EVENTS as $ev) {
                    $bc["events"][$ev] = false;
                }
                foreach ($args as $ev) {
                    if (array_search($ev, BCPlugin::VALID_EVENTS, true) !== false) {
                        if (array_search($ev, $events, true)) continue;
                        $events[] = $ev;
                        $bc["events"][$ev] = true;
                    }
                }
                $plugin->changeBlockCommand($id, $bc);

                $events = count($events) > 0 ? implode(", ", $events) : "NONE";
                $sender->sendMessage($prefix . "Changed $id's events to: " . TF::YELLOW . $events);
                break;
            case "addcommand":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];
                $command = implode(" ", $args);
                if ($command === "") {
                    $sender->sendMessage($error . "Please provide a command.");
                    break;
                }

                $bc["commands"][] = $command;
                $plugin->changeBlockCommand($id, $bc);
                $sender->sendMessage($prefix . "Added Command: " . TF::GRAY . $command);
                break;
            case "listcommands":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];
                $sender->sendMessage($prefix . TF::BOLD . sprintf("%s has %d commands.", $id, count($bc["commands"])));
                foreach ($bc["commands"] as $i => $cmd) {
                    $num = $i + 1;
                    $sender->sendMessage($prefix . "$num: " . TF::GRAY . "$cmd");
                }
                break;
            case "removecommand":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];
                $index = ((int) array_shift($args)) - 1;
                if (!isset($bc["commands"][$index])) {
                    $num = $index + 1;
                    $sender->sendMessage($prefix . "There is no command at index $num.");
                    break;
                }

                $command = $bc["commands"][$index];

                unset($bc["commands"][$index]);
                $plugin->changeBlockCommand($id, $bc);
                $sender->sendMessage($prefix . "Removed Command: " . TF::GRAY . $command);
                break;
            case "clearcommands":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];
                $bc["commands"] = [];

                $plugin->changeBlockCommand($id, $bc);
                $sender->sendMessage($prefix . "Removed all Commands from " . TF::GRAY . $id);
                break;
            case "addblock":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];

                $x = array_shift($args);
                $y = array_shift($args);
                $z = array_shift($args);
                $level = array_shift($args);

                $pos = null;

                if ($x === null) {
                    if (!($sender instanceof Player)) {
                        $sender->sendMessage($error . "Please provide a position");
                        break;
                    }
                    /** @var Player $sender */
                    $pos = $sender->getTargetBlock(10) ?? $sender->getPosition();
                } else {
                    if (!$level && !($sender instanceof Player)) {
                        $sender->sendMessage($error . "Please provide a level.");
                        break;
                    }

                    if (!$level && ($sender instanceof Player) && !($level = $sender->getLevel())) {
                        $sender->sendMessage($error . "Please provide a level.");
                        break;
                    }

                    if (is_string($level) && !($level = $plugin->getServer()->getLevelByName($level))) {
                        $sender->sendMessage($error . "That level does not exist.");
                        break;
                    }

                    $pos = new Position((int) $x, (int) $y, (int) $z, $level);
                }

                if ($pos->getFloorY() < 0) {
                    $sender->sendMessage($error . "Cannot set a block under y=0.");
                }

                $bc["blocks"][] = [
                    "level" => $pos->getLevelNonNull()->getFolderName(),
                    "x" => $pos->getFloorX(),
                    "y" => $pos->getFloorY(),
                    "z" => $pos->getFloorZ()
                ];

                $plugin->changeBlockCommand($id, $bc);
                $sender->sendMessage($prefix . "Added Block: " . TF::GRAY . (string) $pos);
                break;
            case "addarea":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];

                $x1 = array_shift($args);
                $y1 = array_shift($args);
                $z1 = array_shift($args);
                $x2 = array_shift($args);
                $y2 = array_shift($args);
                $z2 = array_shift($args);
                $level = array_shift($args);

                if ($x1 === null || $y1 === null || $z1 === null || $x2 === null || $y2 === null || $z2 === null) {
                    $sender->sendMessage($error . "Please provide two coordinates and a level.");
                    break;
                }

                if (!$level && !($sender instanceof Player)) {
                    $sender->sendMessage($error . "Please provide a level.");
                    break;
                }

                if (!$level && ($sender instanceof Player) && !($level = $sender->getLevel())) {
                    $sender->sendMessage($error . "Please provide a level.");
                    break;
                }

                if (is_string($level) && !($level = $plugin->getServer()->getLevelByName($level))) {
                    $sender->sendMessage($error . "That level does not exist.");
                    break;
                }

                if ((int) $y1 < 0 || (int) $y2 < 0) {
                    $sender->sendMessage($error . "Cannot set an area under y=0.");
                }

                $bc["areas"][] = [
                    "level" => $level->getFolderName(),
                    "x1" => (int) $x1,
                    "y1" => (int) $y1,
                    "z1" => (int) $z1,
                    "x2" => (int) $x2,
                    "y2" => (int) $y2,
                    "z2" => (int) $z2,
                ];

                $plugin->changeBlockCommand($id, $bc);
                $sender->sendMessage($prefix . "Added Area: " . TF::GRAY . "level=" . $level->getFolderName());
                break;
            case "listattachments":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];
                $sender->sendMessage($prefix . TF::BOLD . sprintf("%s has %d attachments.", $id, count($bc["blocks"]) + count($bc["areas"])));
                foreach ($bc["blocks"] as $i => $block) {
                    $num = $i + 1;
                    $sender->sendMessage($prefix . "$num: " . TF::GRAY . str_replace("Array", "Block", print_r($block, true)));
                }
                foreach ($bc["areas"] as $i => $area) {
                    $num = count($bc["blocks"]) + $i + 1;
                    $sender->sendMessage($prefix . "$num: " . TF::GRAY . str_replace("Array", "Area", print_r($area, true)));
                }
                break;
            case "removeattachment":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];
                $index = ((int) array_shift($args)) - 1;
                if (!isset($bc["blocks"][$index])) {
                    $num = $index + 1;
                    if (!isset($bc["areas"][$index - count($bc["blocks"])])) {
                        $sender->sendMessage($error . "There is no attachment at index $num.");
                        break;
                    }
                    unset($bc["areas"][$index - count($bc["blocks"])]);
                    $bc["areas"] = array_values($bc["areas"]);
                    $plugin->changeBlockCommand($id, $bc);
                    $sender->sendMessage($prefix . "Removed Area from " . TF::GRAY . $id);
                    break;
                }
                
                unset($bc["blocks"][$index]);
                $bc["blocks"] = array_values($bc["blocks"]);
                $plugin->changeBlockCommand($id, $bc);
                $sender->sendMessage($prefix . "Removed Block from " . TF::GRAY . $id);
                break;
            case "clearattachments":
                $id = array_shift($args);
                if (!$id) {
                    $sender->sendMessage($error . "Please provide a name for the BlockCommand.");
                    break;
                }

                $bcs = $plugin->getBlockCommands();
                if (!isset($bcs[$id])) {
                    $sender->sendMessage($error . "That BlockCommand does not exist.");
                    break;
                }

                $bc = $bcs[$id];
                $bc["blocks"] = [];
                $bc["areas"] = [];

                $plugin->changeBlockCommand($id, $bc);
                $sender->sendMessage($prefix . "Removed all Blocks and Areas from " . TF::GRAY . $id);
                break;
            default:
                $helpCmd = "";
                if ($subcommand === "help") {
                    $helpCmd = array_shift($args);
                }
                $this->sendEditHelp($sender, $label, $helpCmd);
        }
    }

    private function sendEditHelp(CommandSender $sender, string $label, string $subcommand = "")
    {
        $prefix = BCCommand::PREFIX;
        /** @var BCPlugin */
        $plugin = $this->getPlugin();
        $version = $plugin->getDescription()->getVersion();

        switch (strtolower($subcommand)) {
            case "configure":
                $sender->sendMessage($prefix . "/bc edit configure <name> [...events]: events is a list of events separated by space.");
                break;
            case "addcommand":
                $sender->sendMessage($prefix . "/bc edit addcommand <name> <command>: command may have spaces.");
                break;
            case "listcommands":
                $sender->sendMessage($prefix . "/bc edit listcommands <name>: list the commands on <name>.");
                break;
            case "removecommand":
                $sender->sendMessage($prefix . "/bc edit removecommand <name> <index>: removes the command at <index> from <name>.");
                break;
            case "clearcommands":
                $sender->sendMessage($prefix . "/bc edit clearcommands <name>: removes all the commands from <name>.");
                break;
            case "addblock":
                $sender->sendMessage($prefix . "/bc edit addblock <name> <pos> <level>: <pos> and <level> will be set to the block your looking at or where your standing.");
                break;
            case "addarea":
                $sender->sendMessage($prefix . "/bc edit addarea <name> <pos1> <pos2> <level>: <level> is optional if you send the command as a player.");
                break;
            case "listattachments":
                $sender->sendMessage($prefix . "/bc edit listattachments <name>: lists blocks and areas on <name>.");
                break;
            case "removeattachment":
                $sender->sendMessage($prefix . "/bc edit removeattachment <name> <index>: removes the block or area at <index> from <name>.");
                break;
            case "clearattachments":
                $sender->sendMessage($prefix . "/bc edit clearattachments <name>: removes all blocks and areas from <name>.");
                break;
            default:
                if ($subcommand !== "" && strtolower($subcommand) !== "edit") {
                    $sender->sendMessage($prefix . "`/$label help` only works for `/$label edit ...` commands.");
                    break;
                }
                $sender->sendMessage($prefix . "BlockCommands (v$version) `/$label edit` help:");
                $sender->sendMessage($prefix . "Edit commands " . TF::ITALIC . "(/$label edit <cmd>): " .
                    TF::RESET . TF::YELLOW . "configure, addcommand, ");
                $sender->sendMessage($prefix . TF::YELLOW .
                    "listcommands, removecommand, addblock, addarea, ");
                $sender->sendMessage($prefix . TF::YELLOW .
                    "listattachments, removeattachment, clearattachments");
        }
    }
}
