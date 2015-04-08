<?php

namespace WorldGM;

use pocketmine\utils\Config;
use pocketmine\Server;

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

class Utilities {

    public static function getWorldGamemode(Config $config, $world) {
        return (isset($config->get(WorldGM::CONFIG_WORLDS)[$world])) ? $config->get(WorldGM::CONFIG_WORLDS)[$world] : Server::getInstance()->getDefaultGamemode(); //Yes, bad practice, but it is the only way to get it to work properly. Do not kill me.   
      }

    public static function setWorldGamemode(Config $config, $world, $gamemode) {
        $worlds = $config->get(WorldGM::CONFIG_WORLDS);
        $worlds[$world] = $gamemode;
        $config->set(WorldGM::CONFIG_WORLDS, $worlds);
        $config->save();
    }

    public static function unsetWorldGamemode(Config $config, $world) {
        $worlds = $config->get(WorldGM::CONFIG_WORLDS);
        unset($worlds[$world]);
        $config->set(WorldGM::CONFIG_WORLDS, $worlds);
        $config->save();
    }

    public static function removeprop(Config $config, $arrname, $value) {
        if (in_array(strtolower($value), array_map('strtolower', $conf = $config->get($arrname)))) {
            $config->set($arrname, array_diff($conf, array($value)));
            $config->save();
            return true;
        } else {
            return false;
        }
    }

    public static function addprop(Config $config, $arrname, $value) {
        if (!in_array(strtolower($value), array_map('strtolower', $conf = $config->get($arrname)))) {
            $arr = $config->get($arrname);
            $arr[] = $value;
            $config->set($arrname, $arr);
            $config->save();
            return true;
        } else {
            return false;
        }
    }

}
