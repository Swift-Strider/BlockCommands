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
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class LaunchCommand extends BCCommand
{
    public function __construct(string $name)
    {
        parent::__construct($name, "Launches players in the given direction.");
        $this->setPermission("blockcommands.command.launch");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        $error = BCCommand::ERROR_PREFIX;
        if (!($player = array_shift($args))) {
            $sender->sendMessage($error . "Please specify a player.");
            return true;
        }

        $player = $this->getOwningPlugin()->getServer()->getPlayerByPrefix($player);
        if (!$player) {
            $sender->sendMessage($error . "That player is not online.");
            return true;
        }

        if (count($args) < 3) {
            $sender->sendMessage($error .
                "Please specify a direction in (x y z).");
            return true;
        }

        $this->dealKnockBack($player, (float) array_shift($args), (float) array_shift($args), (float) array_shift($args));
        return true;
    }

    private function dealKnockBack(Player $player, float $x, float $y, float $z)
    {
        $motion = new Vector3($x, $y, $z);
        $player->setMotion($motion);
    }
}
