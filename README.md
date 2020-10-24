# AdvancancedBounty
An alternate bounty plugin for pocketmine using EconomyAPI.

# How to install
1. Download the phar from poggit.
2. Add it to your server's plugin directory.
3. Restart your server!

# Commands + Permissions
- /bounty me - Informs sender of how much bounty is currently on their head.
- /bounty see <player> - Check how much bounty is on the head of another player.
- /bounty add <player> <amount> - Add bounty to a player.
- /bounty reset <player> - Reset the bounty of another player.
  
  Permissions:
      
      bounty.command.me:
      default: true
      description: Allows players to use /bounty me.
      
      bounty.command.see:
      default: true
      description: Allows players to use /bounty see.
      
      bounty.command.add:
      default: true
      description: Allows players to add bounty to other players with /bounty add.
      
      bounty.command.reset:
      default: op
      description: Allows players to reset the bounty of another player.

# Features
- All messages are configurable in messages.yml.
- Configurable cooldown for /bounty add in config.yml.
- Ability to set minimum and maximum bounty added in config.yml.
- Compatibilty with EconomyAPI.
- When a player with a bounty is killed, their bounty is reset to 0 and whatever they were worth goes to the killer.

# API
Get a players bounty worth by first getting this plugin, then do:
    
      $bounty->getBountyWorth($player);
      
Set a players bounty worth with:
     
      $bounty->setBountyWorth($player, $amount);

Claim the bounty with:
      
      $bounty->claimBounty($victim, $killer);
      
# Coming soon
- Cost for adding bounty to players.

# Contact me
Need help or have any suggestions? Contact me on discord TPE#1061 or leave an issue on github or review on poggit, enjoy!
     
