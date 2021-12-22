<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\entities;

use Mcbeany\BetterMinion\minions\MinionInfo;
use Mcbeany\BetterMinion\minions\MinionNBT;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\entity\Human;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class BaseMinion extends Human{

	protected const WORKING_RADIUS = 2; //TODO: Expander upgrade

	protected UuidInterface $owner;
	protected MinionInfo $minionInfo;
	protected SimpleInventory $minionInv;

	protected int $tickWait = 0;
	protected ?Block $workingBlock = null;
	protected bool $isPaused = false;

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->owner = Uuid::uuid3(Uuid::NIL, $nbt->getString(MinionNBT::OWNER));
		$this->minionInfo = MinionInfo::nbtDeserialize($nbt);
		$this->minionInv = new SimpleInventory($this->getMinionInfo()->getLevel());
		$this->getMinionInventory()->setContents(array_map(
			fn(CompoundTag $nbt) : Item => Item::nbtDeserialize($nbt),
			$nbt->getListTag(MinionNBT::INV)?->getValue() ?? []
		));
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setString(MinionNBT::OWNER, $this->getOwner()->toString());
		$nbt->merge($this->getMinionInfo()->nbtSerialize());
		$nbt->setTag(MinionNBT::INV, new ListTag(array_map(
			fn(Item $item) : CompoundTag => $item->nbtSerialize(),
			$this->getMinionInventory()->getContents(true)),
			NBT::TAG_Compound
		));
		return $nbt;
	}

	public function getOwner() : UuidInterface{
		return $this->owner;
	}

	public function getMinionInfo() : MinionInfo{
		return $this->minionInfo;
	}

	public function getMinionInventory() : SimpleInventory{
		return $this->minionInv;
	}

	public function getActionTime() : int{
		return 1; // TODO: Level-based action time
	}

	/**
	 * @return Block[]
	 */
	public function getWorkingBlocks() : array{
		return [];
	}

	protected function isContainAir() : bool{
		$workspace = $this->getWorkingBlocks();
		foreach($workspace as $block){
			if ($block instanceof Air){
				return true;
			}
		}
		return false;
	}

	protected function getAirBlock() : ?Air{
		$workspace = $this->getWorkingBlocks();
		foreach($workspace as $block){
			if ($block instanceof Air){
				return $block;
			}
		}
		return null;
	}

	protected function isContainInvalidBlock() : bool{
		$workspace = $this->getWorkingBlocks();
		foreach($workspace as $block){
			if ($block->getIdInfo() !== $this->getMinionInfo()->getTarget()){
				if (!$block instanceof Air){
					return true;
				}
			}
		}
		return false;
	}

	protected function onAction() : bool{
		return true;
	}

	protected function doOfflineAction(int $times) : bool{
		return true;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		if ($this->isPaused){
			return parent::entityBaseTick($tickDiff);
		}
		$this->tickWait += $tickDiff;
		$actionTime = $this->getActionTime();
		if($this->tickWait >= $actionTime){
			$times = $this->tickWait / $actionTime;
			$this->tickWait -= $actionTime * $times;
			if($this->tickWait == 0){
				if(($times - 1) > 0){
					$this->doOfflineAction($times - 1);
				}
				$hasUpdate = $this->onAction();
			}else{
				$hasUpdate = $this->doOfflineAction($times);
			}
		}
		if(isset($hasUpdate)){
			return $hasUpdate;
		}
		return parent::entityBaseTick($tickDiff);
	}

	protected function getWorkingBlock() : ?Block{
		return $this->workingBlock;
	}

	protected function setWorkingBlock(?Block $block) : void{
		$this->workingBlock = $block;
	}

	protected function getTool() : Item{
		return ItemFactory::air();
	}
}