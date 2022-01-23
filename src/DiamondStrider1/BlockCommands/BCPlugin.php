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

use DiamondStrider1\BlockCommands\commands\management\ManageCommand;
use DiamondStrider1\BlockCommands\commands\misc\LaunchCommand;
use DiamondStrider1\BlockCommands\commands\misc\SudoCommand;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

class BCPlugin extends PluginBase
{
    private static self $instance;

    public static function getInstance(): self {
        return self::$instance;
    }

    const VALID_EVENTS = [
        "punch", "break", "step", "interact"
    ];

    /** @var BCData */
    private $data;
    /** @var BCListener */
    private $listener;

    public function onLoad(): void {
        self::$instance = $this;
    }

    public function onEnable(): void
    {
        $this->reloadConfig();
        $this->data = new BCData($this);
        if (count($errors = $this->data->getErrors()) !== 0) {
            $this->getLogger()->alert(TF::BOLD . "The data.yml file has the following errors (These BlockCommands will not function):");
            $this->getLogger()->alert(str_repeat("-", 25));
            foreach ($errors as $e) {
                $this->getLogger()->alert(TF::YELLOW .   $e);
            }
        }

        $this->listener = new BCListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->listener, $this);

        $this->getServer()->getCommandMap()->registerAll("blockcommands", [
            new ManageCommand("bc"),
            new LaunchCommand("launch"),
            new SudoCommand("sudo"),
        ]);
    }

    public function onDisable(): void
    {
        $this->saveData();
    }

    public function reloadData()
    {
        $this->data->loadConfig();
    }

    public function saveData()
    {
        $this->data->saveConfig();
    }

    public function createBlockCommand(string $id): array
    {
        $events = [];
        foreach (self::VALID_EVENTS as $ev) {
            $events[$ev] = false;
        }
        $this->data->setEntry($id, [
            "commands" => [],
            "blocks" => [],
            "areas" => [],
            "events" => $events
        ]);
        return $this->data->getEntry($id);
    }

    public function changeBlockCommand(string $id, array $data = null): bool
    {
        if (!$data) {
            return $this->data->removeEntry($id);
        } else {
            $this->data->setEntry($id, $data);
            return true;
        }
    }

    public function getBlockCommands(): array
    {
        return $this->data->getEntries();
    }

    public function getBlockCommandsAtPosition(Position $pos): array
    {
        $ret = [];
        $pos->x = $pos->getFloorX();
        $pos->y = $pos->getFloorY();
        $pos->z = $pos->getFloorZ();

        $blockCommands = $this->data->getEntries();
        foreach ($blockCommands as $bc) {
            foreach ($bc["blocks"] as $block) {
                $level = $this->getServer()->getWorldManager()->getWorldByName($block["level"]);
                if (!$level) continue;
                $vector = new Vector3($block["x"], $block["y"], $block["z"]);
                if ($pos->equals($vector)) {
                    $ret[] = $bc;
                    continue 2;
                }
            }
            foreach ($bc["areas"] as $area) {
                $level = $this->getServer()->getWorldManager()->getWorldByName($area["level"]);
                if (!$level || $level !== $pos->getWorld()) continue;
                $vector1 = new Vector3($area["x1"], $area["y1"], $area["z1"]);
                $vector2 = new Vector3($area["x2"], $area["y2"], $area["z2"]);
                if ($this->in_area($pos, $vector1, $vector2)) {
                    $ret[] = $bc;
                    continue 2;
                }
            }
        }
        return $ret;
    }

    private function in_area(Vector3 $subject, Vector3 $pos1, Vector3 $pos2)
    {
        return $this->in_bounds($subject->x, $pos1->x, $pos2->x) &&
            $this->in_bounds($subject->y, $pos1->y, $pos2->y) &&
            $this->in_bounds($subject->z, $pos1->z, $pos2->z);
    }

    private function in_bounds($subject, $num1, $num2)
    {
        return $subject <= max($num1, $num2) && $subject >= min($num1, $num2);
    }
}
