<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Block;
use xenialdan\Overvoltage\TileSource;

class RedstoneBlockTile extends Block implements RedstonePowerSource
{

    public function isSignalSource(): bool
    {
        return true;
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $data): int
    {
        return 15;
    }
}