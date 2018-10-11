<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\WeightedPressurePlateLight;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use xenialdan\Overvoltage\TileSource;

class LightPressurePlateTile extends WeightedPressurePlateLight implements RedstonePowerSource
{
    const EVERYTHING = 0;
    const MOBS = 1;
    private $sensitivity;

    public function __construct(int $id, $sensitivity = self::EVERYTHING, int $meta = 0)
    {
        $this->id = $id;
        $this->sensitivity = $sensitivity;
        parent::__construct($meta);
    }

    public function getDataFromPower(int $power): int
    {
        return $power > 0 ? 1 : 0;
    }

    public function getPowerFromData(int $data): int
    {
        return $data == 1 ? 15 : 0;
    }

    public function getPower(TileSource $region, int $x, int $y, int $z): int
    {
        $aabb = new AxisAlignedBB($x + 0.125, $y, $z + 0.125, ($x + 1) - 0.125, $y + 0.25, ($z + 1) - 0.125);
        $list = $region->getLevel()->getCollidingEntities($aabb);

        if (count($list) <= 0)
            return 0;
        if ($this->sensitivity == self::EVERYTHING || ($this->sensitivity == self::MOBS && $this->_listIncludesMob($list)))
            return 15;

        return 0;
    }

    public function _EntityisMob(Entity $entity): bool
    {
        $id = $entity->getSaveId();
        return $id != 64 && $id != 80 && $id != 81 && $id != 82 && $id != 66;
    }

    public function _listIncludesMob(array $list): bool
    {
        for ($i = 0; $i < count($list); $i++) {
            if ($this->_EntityisMob($list[$i])) return true;
        }
        return false;
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side)
    {
        return $this->getPower($region, $x, $y, $z);
    }
}
