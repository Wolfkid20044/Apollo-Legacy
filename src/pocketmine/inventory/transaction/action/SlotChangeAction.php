<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\inventory\transaction\action;

use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\Player;

/**
 * Represents an action causing a change in an inventory slot.
 */
class SlotChangeAction extends InventoryAction{

	/** @var Inventory|null */
	protected $inventory;
	/** @var int */
	private $inventorySlot;
	/** @var int */
	private $containerId;

	/**
	 * @param Item $sourceItem
	 * @param Item $targetItem
	 * @param int  $containerId
	 * @param int  $inventorySlot
	 */
	public function __construct(Item $sourceItem, Item $targetItem, int $containerId, int $inventorySlot){
		parent::__construct($sourceItem, $targetItem);
		$this->inventorySlot = $inventorySlot;
		$this->containerId = $containerId;
	}

	public function getContainerId() : int{
		return $this->containerId;
	}

	/**
	 * Returns the inventory involved in this action. Will return null if the action has not yet been fully initialized.
	 *
	 * @return Inventory|null
	 */
	public function getInventory(){
		return $this->inventory;
	}

	public function setInventoryFrom(Player $player){
		$inventory = $player->getWindow($this->containerId);
		if($inventory === null){
			throw new \InvalidStateException("Player " . $player->getName() . " has no open container with ID " . $this->containerId);
		}

		$this->inventory = $inventory;
	}

	/**
	 * Returns the slot in the inventory which this action modified.
	 * @return int
	 */
	public function getSlot() : int{
		return $this->inventorySlot;
	}

	/**
	 * Checks if the item in the inventory at the specified slot is the same as this action's source item.
	 *
	 * @param Player $source
	 *
	 * @return bool
	 */
	public function isValid(Player $source) : bool{
		$check = $this->inventory->getItem($this->inventorySlot);
		return $check->equals($this->sourceItem) and $check->getCount() === $this->sourceItem->getCount();
	}

	/**
	 * Checks if the item in the inventory at the specified slot is already the same as this action's target item.
	 *
	 * @return bool
	 */
	public function isAlreadyDone(Player $source) : bool{
		$check = $this->inventory->getItem($this->inventorySlot);
		return $check->equals($this->targetItem) and $check->getCount() === $this->targetItem->getCount();
	}

	/**
	 * Sets the item into the target inventory.
	 *
	 * @param Player $source
	 *
	 * @return bool
	 */
	public function execute(Player $source) : bool{
		return $this->inventory->setItem($this->inventorySlot, $this->targetItem, false);
	}

	/**
	 * Sends slot changes to other viewers of the inventory. This will not send any change back to the source Player.
	 *
	 * @param Player $source
	 */
	public function onExecuteSuccess(Player $source){
		$viewers = $this->inventory->getViewers();
		unset($viewers[spl_object_hash($source)]);
		$this->inventory->sendSlot($this->inventorySlot, $viewers);
	}

	/**
	 * Sends the original slot contents to the source player to revert the action.
	 *
	 * @param Player $source
	 */
	public function onExecuteFail(Player $source){
		$this->inventory->sendSlot($this->inventorySlot, $source);
	}
}
