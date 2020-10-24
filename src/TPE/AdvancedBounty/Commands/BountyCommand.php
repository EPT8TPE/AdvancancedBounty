<?php

declare(strict_types = 1);

namespace TPE\AdvancedBounty\Commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use TPE\AdvancedBounty\AdvancedBounty;

use onebone\economyapi\EconomyAPI;

class BountyCommand extends PluginCommand {

    private $plugin;

    private $msg;

    private $cfg;

    public $bountyCooldownList = [];

    public function __construct(AdvancedBounty $plugin)
    {
        parent::__construct("bounty", $plugin);
        $this->setDescription("Add bounty to a player.");
        $this->setPermission("bounty.command");
        $this->plugin = $plugin;
        $this->msg = new Config($this->plugin->getDataFolder() . "messages.yml", Config::YAML);
        $this->cfg = $this->plugin->getConfig();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender->hasPermission("bounty.command")) {
            $sender->sendMessage($this->msg->get("no-perms-message"));
            return false;
        }

        if(count($args) < 1) {
            $sender->sendMessage($this->msg->get("usage-message"));
            return false;
        }

        if(isset($args[0])) {
            switch(strtolower($args[0])) {
                case "me":
                    if(!$sender->hasPermission("bounty.command.me")) {
                        $sender->sendMessage($this->msg->get("no-perms-bounty-me"));
                        return false;
                    }

                    $currentBounty = $this->plugin->getBountyWorth($sender);

                    if($currentBounty !== 0) {
                        $sender->sendMessage(str_replace("{CURRENT_BOUNTY}", $currentBounty, $this->msg->get("bounty-me-messsage")));
                    } else {
                        $sender->sendMessage($this->msg->get("no-bounty-message"));
                    }
                    return true;

                case "see":

                    if(!$sender->hasPermission("bounty.command.see")) {
                        $sender->sendMessage($this->msg->get("no-perms-bounty-see"));
                        return false;
                    }

                    if(count($args) < 2) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /bounty <see> <player>");
                        return false;
                    }

                    $target = $this->plugin->getServer()->getPlayer($args[1]);

                    if($target == null) {
                        $sender->sendMessage($this->msg->get("target-not-found"));
                        return false;
                    }

                    if($target == true) {
                        $currentBounty = $this->plugin->getBountyWorth($sender);
                        if($currentBounty !== 0) {
                            $sender->sendMessage(str_replace(["{CURRENT_BOUNTY}", "{PLAYER}"], [$currentBounty, $target->getName()], $this->msg->get("bounty-see-message")));
                        } else {
                            $sender->sendMessage(str_replace("{PLAYER}", $target->getName(), $this->msg->get("no-bounty-see-message")));
                        }
                    }
                    return true;

                case "add":
                    
                    if(!$sender->hasPermission("bounty.command.add")) {
                        $sender->sendMessage($this->msg->get("no-perms-bounty-add"));
                        return false;
                    }

                    if(count($args) < 2) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /bounty <add> <player> <amount>");
                        return false;
                    }
                    
                    $target = $this->plugin->getServer()->getPlayer($args[1]);
                    
                    if($target == null) {
                        $sender->sendMessage($this->msg->get("target-not-found"));
                        return false;
                    }

                    if(count($args) < 3) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /bounty <add> <player> <amount>");
                        return false;
                    }
                    
                    if(is_numeric($args[2])) {

                        $amount = (int)$args[2];
                        $balance = EconomyAPI::getInstance()->myMoney($sender);

                        if($balance >= $amount) {
                            if($amount >= $this->plugin->getMinimumBounty() && $amount <= $this->plugin->getMaximumBounty()) {
                                $currentBounty = $this->plugin->getBountyWorth($target);

                                if(!isset($this->bountyCooldownList[strtolower($sender->getName())])) {
                                    $this->bountyCooldownList[strtolower($sender->getName())] = time() + $this->cfg->get("bounty-cooldown");
                                    if($currentBounty !== null) {
                                        $this->plugin->setBountyWorth($target, $amount + $currentBounty);
                                        $current = $this->plugin->getBountyWorth($target);
                                        $sender->sendMessage(str_replace(["{AMOUNT}", "{CURRENT_AMOUNT}", "{PLAYER}"], [$amount, $current, $target->getName()], $this->msg->get("bounty-add-success")));
                                        $target->sendMessage(str_replace(["{AMOUNT}", "{CURRENT_AMOUNT}", "{PLAYER}"], [$amount, $current, $sender->getName()], $this->msg->get("bounty-add-target")));
                                    } else {
                                        $this->plugin->setBountyWorth($target, $amount);
                                        $current = $this->plugin->getBountyWorth($target);
                                        $sender->sendMessage(str_replace(["{AMOUNT}", "{CURRENT_AMOUNT}", "{PLAYER}"], [$amount, $current, $target->getName()], $this->msg->get("bounty-add-success")));
                                        $target->sendMessage(str_replace(["{AMOUNT}", "{CURRENT_AMOUNT}", "{PLAYER}"], [$amount, $current, $sender->getName()], $this->msg->get("bounty-add-target")));
                                    }
                                } else {
                                    if(time() < $this->bountyCooldownList[strtolower($sender->getName())]) {
                                        $remaining = $this->bountyCooldownList[strtolower($sender->getName())] - time();
                                        $sender->sendMessage(str_replace("{REMAINING_TIME}", $remaining, $this->msg->get("cooldown-message")));
                                    } else {
                                        unset($this->bountyCooldownList[strtolower($sender->getName())]);
                                    }
                                }
                            } else {
                                $sender->sendMessage(str_replace(["{MINIMUM_AMOUNT}", "{MAXIMUM_AMOUNT}"], [$this->plugin->getMinimumBounty(), $this->plugin->getMaximumBounty()], $this->msg->get("too-high-too-low-message")));
                            }
                        } else {
                            $sender->sendMessage(str_replace("{MONEY}", $balance, $this->msg->get("not-enough-money")));
                        }
                    } else {
                        $sender->sendMessage($this->msg->get("not-numeric"));
                    }
                    return true;

                case "reset":
                
                    if(!$sender->hasPermission("bounty.command.reset")) {
                        $sender->sendMessage($this->msg->get("no-perms-bounty-reset"));
                        return false;
                    }

                    if(count($args) < 2) {
                        $sender->sendMessage(TextFormat::RED . "Usage /bounty <reset> <player>");
                        return false;
                    }

                    $target = $this->plugin->getServer()->getPlayer($args[1]);
                    if($target == null) {
                        $sender->sendMessage($this->msg->get("target-not-found"));
                        return false;
                    }

                    if($target == true) {
                        $this->plugin->setBountyWorth($target, 0);
                        $sender->sendMessage(str_replace("{PLAYER}", $target->getName(), $this->msg->get("reset-success")));
                    }
                    return true;

                    default:

                    $sender->sendMessage($this->msg->get("usage-message"));
                    return false;
            }
        }
    }
}