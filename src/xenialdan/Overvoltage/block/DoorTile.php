<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Door;
use pocketmine\level\Level;
use pocketmine\Player;
use xenialdan\Overvoltage\Loader;

class DoorTile extends Door implements RedstoneComponent
{

    public function onNearbyBlockChange(): void
    {
        parent::onNearbyBlockChange();

        $powered = Loader::getTileSource($this->getLevel())->isBlockIndirectlyGettingPowered($this->getX(), $this->getY(), $this->getZ());

        $this->setOpen($this->getLevel(), $this->getX(), $this->getY(), $this->getZ(), $powered, NULL);
    }

    public function isOpen(): bool
    {
        return (($this->getDamage() & 0x04) > 0);
    }

    public function setOpen(Level $level, $x, $y, $z, $open = true, ?Player $opener = null)
    {
        if (!$this->isOpen()) {
            $this->onActivate(null, $opener);
        }
    }
}
