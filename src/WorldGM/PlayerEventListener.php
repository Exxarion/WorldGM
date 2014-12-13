<?php

namespace WorldGM;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;

class PlayerEventListener implements Listener {

    private $plugin;

    public function __construct(WorldGM $plugin) {
        $this->plugin = $plugin;
    }

    public function onLevelChange(EntityLevelChangeEvent $event) {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $this->plugin->checkPlayer($entity);
        }
    }


    public function onRespawn(PlayerRespawnEvent $event) {
        $this->plugin->checkPlayer($event->getPlayer());
    }


    public function onQuit(PlayerQuitEvent $event) {
        $this->plugin->checkPlayer($event->getPlayer());
    }

}
