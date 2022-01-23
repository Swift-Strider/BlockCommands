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

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class BCListener implements Listener
{
    /** @var BCPlugin */
    private $plugin;

    /** @var BCCommandSender */
    private $sender;

    public function __construct(BCPlugin $plugin)
    {
        $this->plugin = $plugin;
        $this->sender = new BCCommandSender($plugin);
    }

    public function onPlayerQuit(PlayerQuitEvent $ev)
    {
        $id = $ev->getPlayer()->getUniqueId()->toString();
        if (isset($this->interactDebounce[$id])) {
            unset($this->interactDebounce[$id]);
        }
    }

    /** @var float[] */
    private $interactDebounce = [];
    public function onInteract(PlayerInteractEvent $ev)
    {
        $player = $ev->getPlayer();
        $event = false;
        switch ($ev->getAction()) {
            case PlayerInteractEvent::LEFT_CLICK_BLOCK:
                $event = "punch";
                break;
            case PlayerInteractEvent::RIGHT_CLICK_BLOCK:
                $ticks = $this->plugin->getServer()->getTick();
                $threshold = (float) $this->plugin->getConfig()->get("interact_debounce_time", 1);
                $threshold = (int) ($threshold * 20);
                $id = $player->getUniqueId()->toString();
                if (
                    isset($this->interactDebounce[$id]) &&
                    ($ticks - $this->interactDebounce[$id]) < $threshold
                ) {
                    $event = false;
                } else {
                    $this->interactDebounce[$id] = $ticks;
                    $event = "interact";
                }
                break;
        }
        if (!$event) return;
        $block = $ev->getBlock();
        $bcs = $this->plugin->getBlockCommandsAtPosition($block->getPosition());
        foreach ($bcs as $bc) {
            if (!$bc["events"][$event]) return;
            foreach ($bc["commands"] as $cmd) {
                $this->runBlockCommand($cmd, $player, $block->getPosition());
            }
        }
    }

    public function onBreak(BlockBreakEvent $ev)
    {
        $player = $ev->getPlayer();
        $block = $ev->getBlock();
        $bcs = $this->plugin->getBlockCommandsAtPosition($block->getPosition());
        foreach ($bcs as $bc) {
            if (!$bc["events"]["break"]) return;
            foreach ($bc["commands"] as $cmd) {
                $this->runBlockCommand($cmd, $player, $block->getPosition());
            }
        }
    }

    public function onMove(PlayerMoveEvent $ev)
    {
        $player = $ev->getPlayer();
        $to = $ev->getTo();
        $from = $ev->getFrom();
        if (
            $from->getWorld() === $to->getWorld() &&
            $from->floor()->equals($to->floor())
        ) return;

        $pos = clone $to;
        $pos->y--;

        $bcs = $this->plugin->getBlockCommandsAtPosition($pos);
        foreach ($bcs as $bc) {
            if (!$bc["events"]["step"]) return;
            foreach ($bc["commands"] as $cmd) {
                $this->runBlockCommand($cmd, $player, $pos);
            }
        }
    }


    const PLAYER_PATTERN = "/{player}/";
    const POSITION_PATTERN = "/{position}/";
    private function runBlockCommand($commandLine, Player $player, Vector3 $pos)
    {
        $x = $pos->getFloorX();
        $y = $pos->getFloorY();
        $z = $pos->getFloorZ();

        $commandLine = preg_replace(self::PLAYER_PATTERN, $player->getName(), $commandLine);
        $commandLine = preg_replace(self::POSITION_PATTERN, "$x $y $z", $commandLine);
        $this->plugin->getServer()->dispatchCommand($this->sender, $commandLine);
    }
}
