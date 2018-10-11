<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Trapdoor;
use xenialdan\Overvoltage\Loader;

class TrapDoorTile extends Trapdoor implements RedstoneComponent
{
    public function onNearbyBlockChange(): void
    {
        parent::onNearbyBlockChange();

        $region = Loader::getTileSource($this->getLevel());
        $isPowered = $region->isBlockIndirectlyGettingPowered($this->x, $this->y, $this->z);
        $neighborProvidesPower = false;
        $neighborIndirectlyGettingPower = false;

        if (!$isPowered) {
            foreach ($this->getAllSides() as $side) {
                if ($side instanceof RedstoneComponent || $side instanceof RedstonePowerSource) {
                    if (method_exists($side, 'isSignalSource'))
                        $neighborProvidesPower = $side->isSignalSource();
                    break;
                } elseif ($region->isBlockIndirectlyGettingPowered($side->x, $side->y, $side->z)) {
                    $neighborIndirectlyGettingPower = true;
                    break;
                }
            }
        }

        if ($isPowered || $neighborProvidesPower || !$neighborIndirectlyGettingPower)
            $this->setOpen($isPowered);
    }

    public function isOpen(): bool
    {
        return ($this->getDamage() & self::MASK_OPENED) > 0;
    }

    public function setOpen(bool $open)
    {
        if (($open && !$this->isOpen()) xor (!$open && $this->isOpen()))
            $this->getLevel()->useItemOn($this);
    }
}
