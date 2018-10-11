<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\WeightedPressurePlateHeavy;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use xenialdan\Overvoltage\TileSource;

class HeavyPressurePlateTile extends WeightedPressurePlateHeavy implements RedstonePowerSource
{
    private $weight;

    public function __construct($weight, int $id, int $meta = 0)
    {
        $this->id = $id;
        $this->weight = $weight;
        parent::__construct($meta);
    }

    public function getDataFromPower(int $power): int
    {
        return $power;
    }

    public function getPowerFromData(int $data): int
    {
        return $data;
    }

    public function getTickDelay(): int
    {
        return 10;
    }

    public function getPower(Level $region, int $x, int $y, int $z): int
    {
        $aabb = new AxisAlignedBB($x + 0.125, $y, $z + 0.125,
            ($x + 1) - 0.125, $y + 0.25, ($z + 1) - 0.125);
        $smaller = min((int)count($region->getCollidingEntities($aabb)), $this->weight);

        if ($smaller <= 0)
            return 0;
        else {
            $var6 = (float)min($this->weight, $smaller) / (float)$this->weight;
            return ceil($var6 * 15.0);
        }
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side)
    {
        return $this->getPower($region, $x, $y, $z);
    }
}
