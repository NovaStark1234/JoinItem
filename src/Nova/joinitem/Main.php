<?php

declare(strict_types=1);

namespace Nova\joinitem;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\item\ItemFactory;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\nbt\tag\StringTag;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\data\bedrock\EnchantmentIdMap;

class Main extends PluginBase implements Listener {
	
	private $cfg;
	
	private $data;
	
	public function onEnable() :void {
		@mkdir($this->getDataFolder());
		parent::saveDefaultConfig();
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onJoin(PlayerJoinEvent $event) { 
		$player = $event->getPlayer();
		$pname = $player->getName();
		if(!$this->data->exists($pname)){
			$this->data->set($pname, 0);
			$this->data->save();
		} else {
			return;
		}
		$defItem = explode("|", $this->cfg->get("DefaultItem"));
		if($this->data->exists($pname) && $this->data->get($pname) == 0) {
			$item = ItemFactory::getInstance()->get((int)$defItem[0], (int)$defItem[1], (int)$defItem[4]);
			$item->setCustomName($defItem[2]);
			$item->setLore(array(str_replace("{line}", "\n", (string)$defItem[3])));
			$item->getNamedTag()->setString("JItem", "JoinItem");
			$player->getInventory()->addItem($item);
			$this->data->set($pname, 1);
			$this->data->save();
		} 
	}
		
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) :bool {
		switch($cmd->getName()) {
			case "joinitem":
				if($sender instanceof Player) {
					$defItem = explode("|", $this->cfg->get("DefaultItem"));
					$item = ItemFactory::getInstance()->get((int)$defItem[0], (int)$defItem[1], (int)$defItem[4]);
					$item->setCustomName($defItem[2]);
					$item->setLore(array(str_replace("{line}", "\n", (string)$defItem[3])));
					if(isset($defItem[5]) && isset($defItem[6])) {
						if(is_numeric((int)$listItem[5])) {
							$item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId((int)$defItem[5]), (int)$defItem[6]));
						} else {
							$item->addEnchantment(new EnchantmentInstance(StringToEnchantmentParser::getInstance()->parse((string)$defItem[5]), (int)$defItem[6]));
						}
					}
					$item->getNamedTag()->setString("JItem", "JoinItem");
					$sender->getInventory()->addItem($item);
				}
			break;
		}
		return true;
	}
		
	public function onInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		$pname = $player->getName();
		$itemJoin = $event->getItem();
		
		if ($itemJoin->getNamedTag()->getTag("JItem") === null) {
            		return;
        	}
			
		if($player->getInventory()->getItemInHand()->getCustomName() == explode("|", $this->cfg->get("DefaultItem"))[2]) {
			$player->getInventory()->removeItem($itemJoin);
			foreach($this->cfg->get("ItemList") as $items) {
				$listItem = explode("|", $items);
				$item = ItemFactory::getInstance()->get((int)$listItem[0], (int)$listItem[1], (int)$listItem[4]);
				$item->setCustomName((string)$listItem[2]);
				$item->setLore(array(str_replace("{line}", "\n", (string)$listItem[3])));
				if(isset($listItem[5]) && isset($listItem[6])) {
					if(is_numeric((int)$listItem[5])) {
						$item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId((int)$listItem[5]), (int)$listItem[6]));
					} else {
						$item->addEnchantment(new EnchantmentInstance(StringToEnchantmentParser::getInstance()->parse((string)$listItem[5]), (int)$listItem[6]));
					}
				}
				if($player->getInventory()->canAddItem($item)) {
					$player->getInventory()->addItem($item);
				}
			}
		}
	}
}
