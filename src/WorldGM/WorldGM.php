<?php

namespace WorldGM;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
/*
 __        __         _     _  ____ __  __  
 \ \      / /__  _ __| | __| |/ ___|  \/  | 
  \ \ /\ / / _ \| '__| |/ _` | |  _| |\/| | 
   \ V  V / (_) | |  | | (_| | |_| | |  | | 
    \_/\_/ \___/|_|  |_|\__,_|\____|_|  |_| 
               | |__  _   _                 
               | '_ \| | | |                
               | |_) | |_| |                
  _____        |_.__/ \__, | _              
 | ____|_  ____  ____ |___/_(_) ___  _ __   
 |  _| \ \/ /\ \/ / _` | '__| |/ _ \| '_ \  
 | |___ >  <  >  < (_| | |  | | (_) | | | | 
 |_____/_/\_\/_/\_\__,_|_|  |_|\___/|_| |_| 
                                            
*/                                            
class WorldGM extends PluginBase {
    
    const CONFIG_EXCLUDED = "excluded";
    const CONFIG_WORLDS = "worlds";
    
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);
        $this->getLogger()->info(TextFormat::GREEN."Loaded and enabled successfully");

        $this->saveDefaultConfig();
        $this->reloadConfig();
    }
    
     public function onDisable() {
        $this->getLogger()->info(TextFormat::BLUE."Unloaded and disabled successfully");
        
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
                    case "version":
                        $sender->sendMessage(TextFormat::DARK_GREEN."Your copy of WorldGM is running on v6.1\n".TextFormat::YELLOW.">> Check for updates by going here:\n".TextFormat::GOLD."http://github.com/Exxarion/WorldGM/releases");
                        return true;
                    case "check":
                        $sender->sendMessage($this->checkGmCmd($sender));
                        return true;
                    case "gm":
                        $sender->sendMessage(TextFormat::GREEN."Survival = 0\n".TextFormat::YELLOW."Creative = 1\n".TextFormat::AQUA."Adventure = 2");
                        return true;
                    default:
                        $sender->sendMessage(TextFormat::YELLOW."-------------------\n".TextFormat::GREEN."WorldGM - Version 6.1\n".TextFormat::BLUE."Set Different gamemodes for different worlds\n".TextFormat::DARK_GREEN."Usages:\n".TextFormat::AQUA."/wgm set <0/1/2> <world>\n".TextFormat::AQUA."/wgm <include/exclude> <player>\n".TextFormat::AQUA."/wgm version\n".TextFormat::AQUA."/wgm check\n".TextFormat::AQUA."/wgm gm\n".TextFormat::DARK_RED."- Created by Exxarion\n".TextFormat::YELLOW."-------------------");
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
        
        if (($gamemodeTo = Server::getGamemodeFromString($worldGamemode)) == -1) {
            $this->getLogger()->warning($worldGamemode . ' is not a gamemode, until this is fixed, this plugin will use your default gamemode (Set in server.properties) instead.');
            $gamemodeTo = Server::getDefaultGamemode();
        }
        
        $gmneedschanging = $player->getGamemode() !== ($gamemodeTo);
        
        if (!$isExcluded && $gmneedschanging) {

            $player->setGamemode($gamemodeTo);
            $player->sendMessage(TextFormat::GREEN."[WorldGM] Your gamemode has been changed to $gamemodeTo");
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
                    return TextFormat::YELLOW."[WorldGM] Please specify a world";
                }
            } else {
                return TextFormat::YELLOW."[WorldGM] Please specify a gamemode\n [Survival = 0/Creative = 1/Adventure = 2]";
            }
        } elseif (count($params) == 2) {

            if (($mode = Server::getGamemodeFromString($params[0])) !== -1 && $params[0] != "none") {

                if ($this->getServer()->getLevel($params[1]) == null) {
                    $world = $params[1];
                } else {
                    return TextFormat::RED."[WorldGM] That world does not exist.";
                }
            } elseif (($mode = Server::getGamemodeFromString($params[1])) !== -1 && $params[0] != "none") {

                if ($this->getServer()->getLevel($params[0]) == null) {
                    $world = $params[0];
                } else {
                    return TextFormat::RED."[WorldGM] That world does not exist";
                }
            } else {
                return TextFormat::RED."[WorldGM] Please specify a gamemode\n [Survival = 0/Creative = 1/Adventure = 2]";
            }
        } else {
            return TextFormat::YELLOW."Usage: /wgm set <gamemode> (world)";
        }


        Utilities::setWorldGamemode($this->getConfig(), $world, $mode);
        $this->checkAllPlayers($world);
        return TextFormat::GREEN."[WorldGM] The gamemode of $world has been set to $mode.\n".TextFormat::YELLOW."[WorldGM] Please run".TextFormat::RED." /stop".TextFormat::YELLOW." in order for changes to go into effect.";
    }

    public function excludePlayerCmd($sender, $params) {

        if (is_null($playerpar = array_shift($params))) {
            return TextFormat::YELLOW."Usage: /wgm exclude <player>";
        }
        if (null !== $player = $this->getServer()->getPlayer($playerpar)) {
            if (Utilities::addprop($this->getConfig(), WorldGM::CONFIG_EXCLUDED, $player->getName())) {
                return TextFormat::GREEN.$player->getName() . " will not be affected by world gamemode changes";
            }
        if ($player->isOP()){ //Auto-excludes OPs. In testing.
            if (Utilities::addprop($this->getConfig(), WorldGM::CONFIG_EXCLUDED, $player->getName())) {
            }
            } else {
                return TextFormat::YELLOW.$player->getName() . " has already been excluded";
            }
        } else {
            return TextFormat::RED."[WorldGM] $playerpar is currently offline";
        }
    }

    public function includePlayerCmd($sender, $params) {

        if (is_null($playerpar = array_shift($params))) {
            return TextFormat::YELLOW."Usage: /wgm include <player>";
        }
        if (null !== $player = $this->getServer()->getPlayer($playerpar)) {
            if (Utilities::removeprop($this->getConfig(), WorldGM::CONFIG_EXCLUDED, $player->getName())) {
                $this->checkPlayer($player);
                return TextFormat::GREEN.$player->getName() . " will be affected by world gamemode changes";
                
            } else {
                return TextFormat::YELLOW.$player->getName() . " has already been included";
            }
        } else {
            return TextFormat::RED."[WorldGM] $playerpar is currently offline";
        }
    }

    public function checkGmCmd($sender) {
      $sender->sendMessage(TextFormat::AQUA."[WorldGM] Online Player Gamemodes:");
            foreach($sender->getServer()->getOnlinePlayers() as $allplayers){
            	$world = $allplayers->getLevel()->getName();
                if($allplayers->isCreative()){
                    $sender->sendMessage(TextFormat::DARK_GREEN.$allplayers->getName()." is in gamemode ".TextFormat::YELLOW."Creative".TextFormat::AQUA." while in world ".TextFormat::GOLD."$world.");
                }
                elseif($allplayers->isSurvival()){
                    $sender->sendMessage(TextFormat::DARK_GREEN.$allplayers->getName()." is in gamemode ".TextFormat::GREEN."Survival".TextFormat::AQUA." while in world ".TextFormat::GOLD."$world.");
                }
                elseif($allplayers->isAdventure()){
                    $sender->sendMessage(TextFormat::DARK_GREEN.$allplayers->getName()." is in gamemode ".TextFormat::BLUE."Adventure".TextFormat::AQUA." while in world ".TextFormat::GOLD."$world.");
                }
		}
	}
}
//Plugin created by Exxarion. Registered under GNU GENERAL PUBLIC LICENSE. Do not distribute!
