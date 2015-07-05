<?php

namespace WorldGM;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
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
            case "s":
        	$sender->setGamemode(0);
                return true;
            case "c":
        	$sender->setGamemode(1);
                return true;

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
                        $sender->sendMessage(TextFormat::DARK_GREEN."Your copy of WorldGM is running on v8.0\n".TextFormat::YELLOW.">> Check for updates by running this command:\n".TextFormat::GOLD."/wgm update");
                        return true;
                    case "check":
                        $sender->sendMessage($this->checkGmCmd($sender));
                        return true;
                    case "gm":
                        $sender->sendMessage(TextFormat::GREEN."Survival = 0\n".TextFormat::YELLOW."Creative = 1\n".TextFormat::AQUA."Adventure = 2");
                        return true;
                    case "update":
                    	$sender->sendMessage($this->updatePlugin($sender));
                    	return true;
                    default:
                        $sender->sendMessage(TextFormat::YELLOW."+-------------------+\n".TextFormat::GREEN."WorldGM - Version 8.0\n".TextFormat::BLUE."Set Different gamemodes for different worlds\n".TextFormat::DARK_GREEN."Usages:\n".TextFormat::AQUA."/wgm set <0/1/2> <world>\n".TextFormat::AQUA."/wgm <include/exclude> <player>\n".TextFormat::AQUA."/wgm version\n".TextFormat::AQUA."/wgm check\n".TextFormat::AQUA."/wgm gm\n".TextFormat::AQUA."/wgm update\n".TextFormat::DARK_RED."- Created by Exxarion\n".TextFormat::YELLOW."+-------------------+");
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
        
        $gmNeedsChanging = $player->getGamemode() !== ($worldGamemode);
        
        if (!$isExcluded && $gmNeedsChanging) {

            $player->setGamemode($worldGamemode);
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
        return TextFormat::GREEN."[WorldGM] The gamemode of $world has been set to $mode.\n";
    }

    public function excludePlayerCmd($sender, $params) {

        if (is_null($playerpar = array_shift($params))) {
            return TextFormat::YELLOW."Usage: /wgm exclude <player>";
        }
        if (null !== $player = $this->getServer()->getPlayer($playerpar)) {
            if (Utilities::addprop($this->getConfig(), WorldGM::CONFIG_EXCLUDED, $player->getName())) {
                return TextFormat::GREEN.$player->getName() . " will not be affected by world gamemode changes";
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

    public function updatePlugin($sender) { //BETA
    $this->getLogger()->info(TextFormat::GREEN."Now checking for plugin updates...");
				$lst = Utils::getURL("https://raw.githubusercontent.com/Exxarion/WorldGM/master/plugin.yml");
				
				$dsc = \yaml_parse($lst);
				
				$description = $this->getDescription();
				if($description->getVersion() !== $dsc['version']){
					$this->getLogger()->info(TextFormat::YELLOW."WorldGM v".$dsc["version"]." has been released. Please download the latest version here:\n".TextFormat::GOLD."http://github.com/Exxarion/WorldGM/releases");
				}else{
					$this->getLogger()->info(TextFormat::GREEN."Your version is up-to-date!");
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
