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

namespace DiamondStrider1\BlockCommands\commands;

use DiamondStrider1\BlockCommands\BCPlugin;
use pocketmine\command\Command;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat as TF;

abstract class BCCommand extends Command implements PluginOwned
{
    const PREFIX = TF::YELLOW . "{BlockCommands} " . TF::GREEN;
    public const ERROR_PREFIX = self::PREFIX . TF::RED;

    public function getOwningPlugin(): BCPlugin
    {
        return BCPlugin::getInstance();
    }
}
