<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\FenceGate;
use pocketmine\level\sound\DoorSound;
use xenialdan\Overvoltage\Loader;

class FenceGateTile extends FenceGate implements RedstoneComponent
{

    public function onNearbyBlockChange(): void
    {
        parent::onNearbyBlockChange();
        $data = $this->getDamage();
        $powered = Loader::getTileSource($this->getLevel())->isBlockIndirectlyGettingPowered($this->getX(), $this->getY(), $this->getZ());

        if ($powered && !$this->isOpen($data)) {
            $this->getLevel()->setBlockDataAt($this->getX(), $this->getY(), $this->getZ(), $data | 4);
            $this->getLevel()->addSound(new DoorSound($this->add(0.5, 0.5, 0.5)));
        }
        if (!$powered && $this->isOpen($data)) {
            $this->getLevel()->setBlockDataAt($this->getX(), $this->getY(), $this->getZ(), $data & -5);
            $this->getLevel()->addSound(new DoorSound($this->add(0.5, 0.5, 0.5)));
        }
    }

    public function isOpen(int $data): bool
    {
        return (($data & 0x04) > 0);
    }
}
