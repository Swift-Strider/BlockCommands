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
                break;
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

        $sender->sendMessage($prefix . "Edit commands " . TF::ITALIC . "(/$label <cmd>): " .
            TF::RESET . TF::YELLOW . "configure, addcommand, ");
        $sender->sendMessage($prefix . TF::YELLOW .
            "listcommands, removecommand, addblock, ");
        $sender->sendMessage($prefix . TF::YELLOW .
            "addarea, removecommand, addblock, ");
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
            $sender->sendMessage($prefix . "$id: " . TF::WHITE . "(" .
                TF::YELLOW . implode(TF::WHITE . ", " . TF::YELLOW, $events) .
                TF::WHITE . ")");
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
}
