<?php

namespace WorldGM;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;

class WorldGM extends PluginBase {

    private $utilities;

    const CONFIG_EXCLUDED = "excluded";
    const CONFIG_WORLDS = "worlds";

    public function __construct() {
        $this->utilities = new Utilities($this);
    }

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);
    
        $this->saveDefaultConfig();
        $this->reloadConfig();
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        switch ($command->getName()) {
            case "wgm":
                switch (array_shift($args)) {
                    case "set":
                        $sender->sendMessage($this->setWorldCmd($sender, $args));
                        return true;
                    case "exclude":
                        $sender->sendMessage($this->excludePlayerCmd($sender, $args));
                        return true;
                    case "include":
                        $sender->sendMessage($this->includePlayerCmd($sender, $args));
                        return true;
                    default:
                        $sender->sendMessage("\nWorldGM v3.0 by Exxarion\nUsage: /wgm set <0/1/2>\n/wgm <include/exclude> <player>\n*Must be run as a player*");
                        return true;
                        
                }
            default:
                return false;
        }
    }

    public function checkAllPlayers($world){
        if (is_string($world)) {
            $world = $this->getServer()->getLevelByName($world);
        }
        if (!($world instanceof Level)) {
            return false;
        }
        
        $players = $world->getPlayers();
        
        foreach ($players as $player) {
            $this->checkPlayer($player);
        }
        
    }
    
    public function checkPlayer($player) {

        if (is_string($player)) {
            $player = $this->getServer()->getPlayerExact($player);
        }
        
        if (!($player instanceof Player)) {
            return false;
        }
        

        $world = $player->getLevel()->getName();

        $isExcluded = in_array(strtolower($player->getName()), array_map('strtolower', $this->getConfig()->get(WorldGM::CONFIG_EXCLUDED)));
        $worldGamemode = Utilities::getWorldGamemode($this->getConfig(), $world);
        
        if ($worldGamemode == "none") {
            $gamemodeTo = false;
        } else if (($gamemodeTo = Server::getGamemodeFromString($worldGamemode)) == -1) {
            $this->getLogger()->warning($worldGamemode . ' is not a valid gamemode! (WorldGM/config.yml)\n Using the default gamemode instead');
            $gamemodeTo = Server::getDefaultGamemode();
        }
        
        $gamemodeNeedsChanged = $player->getGamemode() !== ($gamemodeTo);
        
        if (!$isExcluded && ($gamemodeTo !== false) && $gamemodeNeedsChanged) {

            $player->setGamemode($gamemodeTo);
        } else {
            return false;
        }
    }

    public function setWorldCmd($sender, $params) {
        if (count($params) == 1) {
            if (($mode = Server::getGamemodeFromString($params[0])) !== -1 && $params[0] != "none") {

                if ($sender instanceof Player) {
                    $world = $sender->getLevel()->getName();
                } else {
                    return "[WorldGM] Please specify a world";
                }
            } else {
                return "[WorldGM] Please specify a gamemode\n [Survival, Creative, or Adventure]";
            }
        } elseif (count($params) == 2) {

            if (($mode = Server::getGamemodeFromString($params[0])) !== -1 && $params[0] != "none") {

                if ($this->getServer()->getLevel($params[1]) !== null) {
                    $world = $params[1];
                } else {
                    return "[WorldGM] That world does not exist.";
                }
            } elseif (($mode = Server::getGamemodeFromString($params[1])) !== -1 && $params[0] != "none") {

                if ($this->getServer()->getLevel($params[0]) == null) {
                    $world = $params[0];
                } else {
                    return "[WorldGM] That world does not exist";
                }
            } else {
                return "[WorldGM] Please specify a gamemode\n [Survival, Creative, or Adventure]";
            }
        } else {
            return "Usage: /wgm set <gamemode>";
        }


        Utilities::setWorldGamemode($this->getConfig(), $world, $mode);
        $this->checkAllPlayers($world);
        return "[WorldGM] $world's gamemode has been set to $mode.";
    }

    public function excludePlayerCmd($sender, $params) {

        if (is_null($playerpar = array_shift($params))) {
            return "Usage: /wgm exclude <player>";
        }
        if (null !== $player = $this->getServer()->getPlayer($playerpar)) {
            if (Utilities::addprop($this->getConfig(), WorldGM::CONFIG_EXCLUDED, $player->getName())) {
                return $player->getName() . " will not be affected by world gamemode changes";
            } else {
                return $player->getName() . " has already been excluded";
            }
        } else {
            return "$playerpar is currently offline";
        }
    }

    public function includePlayerCmd($sender, $params) {

        if (is_null($playerpar = array_shift($params))) {
            return "Usage: /wgm include <player>";
        }
        if (null !== $player = $this->getServer()->getPlayer($playerpar)) {
            if (Utilities::removeprop($this->getConfig(), WorldGM::CONFIG_EXCLUDED, $player->getName())) {
                $this->checkPlayer($player);
                return $player->getName() . " will be affected by world gamemode changes";
                
            } else {
                return $player->getName() . " has already been included";
            }
        } else {
            return "$playerpar is currently offline";
        }
    }

}

