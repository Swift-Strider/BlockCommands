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

namespace DiamondStrider1\BlockCommands;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\lang\TextContainer;
use pocketmine\utils\TextFormat as TF;

class BCCommandSender extends ConsoleCommandSender
{
    /** @var BCPlugin */
    private $plugin;

    public function __construct(BCPlugin $plugin)
    {
        $this->plugin = $plugin;
        parent::__construct();
    }

    public function sendMessage($message)
    {
        if (!$this->plugin->getConfig()->get("send_blockcommand_output")) return;
        if ($message instanceof TextContainer) {
            $message = $this->getServer()->getLanguage()->translate($message);
        } else {
            $message = $this->getServer()->getLanguage()->translateString($message);
        }

        foreach (explode("\n", trim($message)) as $line) {
            $this->plugin->getLogger()->info(TF::GOLD . "[BlockCommandOutput] " . $line);
        }
    }

    public function getName(): string
    {
        return "BlockCommand";
    }
}
