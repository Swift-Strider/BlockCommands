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

namespace DiamondStrider1\BlockCommands\commands\misc;

use DiamondStrider1\BlockCommands\BCPlugin;
use DiamondStrider1\BlockCommands\commands\BCCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class SudoCommand extends BCCommand
{
    public function __construct(string $name, BCPlugin $owner)
    {
        parent::__construct($name, $owner);
        $this->setDescription("Sends messages and commands as another player.");
        $this->setPermission("blockcommands.command.sudo");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        $error = BCCommand::ERROR_PREFIX;
        if (count($args) < 1) {
            $sender->sendMessage($error . "Please specify a player.");
            return true;
        }

        $player = $this->getPlugin()->getServer()->getPlayer($args[0]);
        if (!$player) {
            $sender->sendMessage($error . "That player is not online.");
            return true;
        }

        if (count($args) < 2) {
            $sender->sendMessage($error .
                "Please specify a message or command (prefixed with `/`).");
            return true;
        }

        $msgStrings = array_splice($args, 1);
        $msg = implode(" ", $msgStrings);

        $player->chat($msg);

        return true;
    }
}
