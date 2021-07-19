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

use ErrorException;
use pocketmine\utils\Config;

class BCData
{
    /** @var BCPlugin */
    private $plugin;
    /** @var Config */
    private $data;
    /** @var array */
    private $workingEntries = [];
    /** @var array */
    private $brokenEntries = [];
    /** @var array */
    private $errors = [];

    public function __construct(BCPlugin $plugin)
    {
        $this->plugin = $plugin;

        $this->loadConfig();
    }

    public function loadConfig()
    {
        $dataPath = $this->plugin->getDataFolder() . "data.yml";
        try {
            $this->data = new Config($dataPath);
        } catch (ErrorException $e) {
            // Data file had syntax errors - Move File
            $dataFile = fopen($dataPath, "r");
            $corruptFile = fopen($dataPath . ".corrupted", "w");
            stream_copy_to_stream($dataFile, $corruptFile);
            fclose($corruptFile);
            fclose($dataFile);

            file_put_contents($dataPath, "");

            $this->plugin->getLogger()->alert("Data file was corrupted! Moved file to data.yml.corrupted!");
            $this->data = new Config($dataPath);
        }
        $this->validate();
    }

    public function saveConfig()
    {
        $all = array_merge($this->brokenEntries, $this->workingEntries);
        $this->data->setAll($all);
        $this->data->save();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getEntries(): array
    {
        return $this->workingEntries;
    }

    private function validate()
    {
        $errors = [];
        $data = $this->data->getAll();

        foreach ($data as $name => $bc) {
            if (!is_array($bc)) {
                $this->brokenEntries[$name] = $bc;
                $errors[] = "BlockCommand $name: data is not an array.";
                continue;
            }

            $hasErrors = false;
            if (!isset($bc["commands"])) {
                $errors[] = "BlockCommand $name: `commands` does not exist.";
                $hasErrors = true;
            } elseif (!is_array($bc["commands"])) {
                $errors[] = "BlockCommand $name: `commands` is not an array.";
                $hasErrors = true;
            } else {
                foreach ($bc["commands"] as $cmd) {
                    if (!is_string($cmd)) {
                        $errors[] = "BlockCommand $name: `commands` holds a non-string.";
                        $hasErrors = true;
                    }
                }
            }

            if (!isset($bc["blocks"])) {
                $errors[] = "BlockCommand $name: `blocks` does not exist.";
                $hasErrors = true;
            } elseif (!is_array($bc["blocks"])) {
                $errors[] = "BlockCommand $name: `blocks` is not an array.";
                $hasErrors = true;
            } else {
                foreach ($bc["blocks"] as $block) {
                    if (!is_array($block)) {
                        $errors[] = "BlockCommand $name: `blocks` holds a non-array.";
                        $hasErrors = true;
                        continue;
                    }

                    if (!isset($block["level"])) {
                        $errors[] = "BlockCommand $name: A `blocks` entry does not have a level.";
                        $hasErrors = true;
                    } elseif (!is_string($block["level"])) {
                        $errors[] = "BlockCommand $name: A `blocks` entry's `level` is not a string.";
                        $hasErrors = true;
                    }

                    if (!isset($block["x"]) || !isset($block["y"]) || !isset($block["z"])) {
                        $errors[] = "BlockCommand $name: A `blocks` entry is missing an x, y, or z.";
                        $hasErrors = true;
                    } else if (!$this->is_num($block["x"]) || !$this->is_num($block["y"]) || !$this->is_num($block["z"])) {
                        $errors[] = "BlockCommand $name: A `blocks` entry does not have numeric coordinates.";
                        $hasErrors = true;
                    }
                }
            }

            if (!isset($bc["areas"])) {
                $errors[] = "BlockCommand $name: `areas` does not exist.";
                $hasErrors = true;
            } elseif (!is_array($bc["areas"])) {
                $errors[] = "BlockCommand $name: `areas` is not an array.";
                $hasErrors = true;
            } else {
                foreach ($bc["areas"] as $area) {
                    if (!is_array($area)) {
                        $errors[] = "BlockCommand $name: `areas` holds a non-array.";
                        $hasErrors = true;
                        continue;
                    }

                    if (!isset($area["level"])) {
                        $errors[] = "BlockCommand $name: An `areas` entry does not have a level.";
                        $hasErrors = true;
                    } elseif (!is_string($area["level"])) {
                        $errors[] = "BlockCommand $name: An `areas` entry's `level` is not a string.";
                        $hasErrors = true;
                    }

                    if (
                        !isset($area["x1"]) || !isset($area["y1"]) || !isset($area["z1"]) ||
                        !isset($area["x2"]) || !isset($area["y2"]) || !isset($area["z2"])
                    ) {
                        $errors[] = "BlockCommand $name: An `areas` entry is missing an x1, y1, z1, x2, y2, or z2.";
                        $hasErrors = true;
                    } elseif (
                        !$this->is_num($area["x1"]) || !$this->is_num($area["y1"]) || !$this->is_num($area["z1"]) ||
                        !$this->is_num($area["x2"]) || !$this->is_num($area["y2"]) || !$this->is_num($area["z2"])
                    ) {
                        $errors[] = "BlockCommand $name: An `areas` entry does not have numeric coordinates.";
                        $hasErrors = true;
                    }
                }
            }

            if (!isset($bc["events"])) {
                $errors[] = "BlockCommand $name: `events` does not exist.";
                $hasErrors = true;
            } else if (!is_array($bc["events"])) {
                $errors[] = "BlockCommand $name: `events` is not an array.";
                $hasErrors = true;
            } else {
                foreach ($bc["events"] as $event => $enabled) {
                    if (array_search($event, BCPlugin::VALID_EVENTS, true) === false) {
                        $errors[] = "BlockCommand $name: The `events` entry $event is not a valid event.";
                        $hasErrors = true;
                    }
                    if (!is_bool($enabled)) {
                        $errors[] = "BlockCommand $name: The `events` entry $event is not a boolean.";
                        $hasErrors = true;
                    }
                }

                foreach (BCPlugin::VALID_EVENTS as $event) {
                    if (!isset($bc["events"][$event])) $bc["events"][$event] = false;
                }
            }

            if ($hasErrors) {
                $this->brokenEntries[$name] = $bc;
                continue;
            }

            $this->workingEntries[$name] = $bc;
        }

        $this->errors = $errors;
    }

    private function is_num($var)
    {
        return is_float($var) || is_int($var);
    }
}
