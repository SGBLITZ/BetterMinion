<?php

declare(strict_types=1);

namespace Mcbeany\BetterMinion\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class MinionSpawnEvent extends MinionEvent implements Cancellable{
	use CancellableTrait;

	public function isOwn() : bool{
		return true;
	}

}