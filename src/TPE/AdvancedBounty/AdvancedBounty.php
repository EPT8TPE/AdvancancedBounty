<?php

declare(strict_types = 1);

namespace TPE\AdvancedBounty;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use TPE\AdvancedBounty\Commands\BountyCommand;
use pocketmine\Player;
use pocketmine\utils\Config;

use onebone\economyapi\EconomyAPI;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;

class AdvancedBounty extends PluginBase implements Listener{

    /**
     * @var Config
     */
    private $dataConfig;

    public function onEnable()
    {
        $this->getServer()->getCommandMap()->register("AdvancedBounty", new  BountyCommand($this));
        $this->saveResource("messages.yml");
        $this->dataConfig = new Config($this->getDataFolder() . "data.sl", Config::SERIALIZED);
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable()
    {
        $this->saveResource("messages.yml");
        $this->saveDefaultConfig();
        $this->dataConfig->save();
    }

    public function claimBounty(Player $player, Player $killer) {
        $currentBounty = $this->getBountyWorth($player);
        if($currentBounty !== 0) {
            EconomyAPI::getInstance()->addMoney($killer->getName(), (float)$currentBounty);
            $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
            $this->getServer()->broadcastMessage(str_replace(["{PLAYER}", "{KILLER}", "{AMOUNT}"], [$player->getName(), $killer->getName(), $currentBounty], $msg->get("bounty-claimed")));
            $this->setBountyWorth($player, 0);
        }
    }

    public function onDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if($killer instanceof Player) {
                $amount = $this->getBountyWorth($player);
                $this->claimBounty($player, $killer);
                $msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
                $killer->sendMessage(str_replace(["PLAYER}", "{AMOUNT}"], [$player->getName(), $amount], $msg->get("killer-message")));
                $player->sendMessage(str_replace("{AMOUNT}", $amount, $msg->get("victim-message")));
            }       
        }   
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        if(!$this->getDataConfig()->get(strtolower($player->getName()))) {
            $this->getDataConfig()->set(strtolower($player->getName()), ["worth" => 0]);
        }
    }

    public function getDataConfig() : Config {
        return $this->dataConfig;
    }

    public function getBountyWorth(Player $player) : int {
        return $this->dataConfig->getNested(strtolower($player->getName()) . ".worth", 0);
    }

    public function setBountyWorth(Player $player, int $amount) {
        $this->dataConfig->setNested(strtolower($player->getName()) . ".worth", $amount);
    }

    public function getMinimumBounty() : int {
        return $this->getConfig()->get("minimum-amount");
    }

    public function getMaximumBounty() : int {
        return $this->getConfig()->get("maximum-amount");
    }

}