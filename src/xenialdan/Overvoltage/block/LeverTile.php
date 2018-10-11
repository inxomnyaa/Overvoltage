<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\BlockFactory;
use pocketmine\block\Lever;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\sound\ClickSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use xenialdan\Overvoltage\Loader;
use xenialdan\Overvoltage\TileSource;

class LeverTile extends Lever implements RedstonePowerSource
{

    public function getPlacementDataValue(Entity $placer, int $x, int $y, int $z, int $side, float $xx, float $yy, float $zz, int $ii): int
    {
        $power = $placer->getLevel()->getBlockDataAt($x, $y, $z) & 8;
        $sides = [7, 5, 4, 3, 2, 1];
        return $sides[$side] + $power;
    }

    public function onActivate(Item $item, Player $player = null): bool
    {
        return $this->use($player, $this->x, $this->y, $this->z);
    }

    public function use(Player $player, int $x, int $y, int $z): bool
    {
        $data = $player->getLevel()->getBlockDataAt($x, $y, $z);
        $rot = $data & 7;
        $power = 8 - ($data & 8);
        $player->getLevel()->setBlock(new Vector3($x, $y, $z), BlockFactory::get(69, $rot + $power));
        $player->getLevel()->addSound(new ClickSound($this->add(0.5, 0.5, 0.5), ($power == 0) ? 0.5 : 0.6));
        $player->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y, $z));
        if ($rot == 1) $player->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x - 1, $y, $z));
        else if ($rot == 2) $player->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x + 1, $y, $z));
        else if ($rot == 3) $player->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y, $z - 1));
        else if ($rot == 4) $player->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y, $z + 1));
        else if ($rot == 5) $player->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y - 1, $z));
        else if ($rot == 7) $player->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y + 1, $z));
        return true;
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        return (($region->getLevel()->getBlockDataAt($x, $y, $z) & 8) > 0) ? 15 : 0;
    }

    public function getSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        $data = $region->getLevel()->getBlockDataAt($x, $y, $z);
        if (($data & 8) == 0) return 0;

        $rot = $data & 7;
        if ($rot == 0 && $side == 0) return 15;
        if ($rot == 7 && $side == 0) return 15;
        if ($rot == 6 && $side == 1) return 15;
        if ($rot == 5 && $side == 1) return 15;
        if ($rot == 4 && $side == 2) return 15;
        if ($rot == 3 && $side == 3) return 15;
        if ($rot == 2 && $side == 4) return 15;
        if ($rot == 1 && $side == 5) return 15;
        return 0;
    }

    public function getVisualShape(TileSource $region, int $x, int $y, int $z, AxisAlignedBB $aabb, bool $b): AxisAlignedBB
    {
        $f = 0.25;
        $f1 = 0.1875;
        $f2 = 0.1875;
        switch ($region->getLevel()->getBlockDataAt($x, $y, $z) & 7) {
            case 1:
                $aabb->setBounds(0.0, 0.5 - $f, 0.5 - $f1, $f2, 0.5 + $f, 0.5 + $f1);
                break;
            case 2:
                $aabb->setBounds(1.0 - $f2, 0.5 - $f, 0.5 - $f1, 1.0, 0.5 + $f, 0.5 + $f1);
                break;
            case 3:
                $aabb->setBounds(0.5 - $f1, 0.5 - $f, 0.0, 0.5 + $f1, 0.5 + $f, $f2);
                break;
            case 4:
                $aabb->setBounds(0.5 - $f1, 0.5 - $f, 1.0 - $f2, 0.5 + $f1, 0.5 + $f, 1.0);
                break;
            case 5:
                $aabb->setBounds(0.5 - $f1, 0.0, 0.5 - $f, 0.5 + $f1, $f2, 0.5 + $f);
                break;
            case 7:
                $aabb->setBounds(0.5 - $f1, 1.0 - $f2, 0.5 - $f, 0.5 + $f1, 1.0, 0.5 + $f);
                break;
        }
        return $aabb;
    }

    public function mayPlace(TileSource $region, int $x, int $y, int $z, int $side): bool
    {
        switch ($side) {
            case 0:
                return $region->getLevel()->getBlockAt($x, $y + 1, $z)->isSolid();
            case 1:
                return $region->getLevel()->getBlockAt($x, $y - 1, $z)->isSolid();
            case 2:
                return $region->getLevel()->getBlockAt($x, $y, $z + 1)->isSolid();
            case 3:
                return $region->getLevel()->getBlockAt($x, $y, $z - 1)->isSolid();
            case 4:
                return $region->getLevel()->getBlockAt($x + 1, $y, $z)->isSolid();
            case 5:
                return $region->getLevel()->getBlockAt($x - 1, $y, $z)->isSolid();
        }
        return false;
    }

    public function canSurvive(TileSource $region, int $x, int $y, int $z): bool
    {
        switch ($region->getLevel()->getBlockDataAt($x, $y, $z) & 7) {
            case 7:
                return $region->getLevel()->getBlockAt($x, $y + 1, $z)->isSolid();
            case 5:
                return $region->getLevel()->getBlockAt($x, $y - 1, $z)->isSolid();
            case 4:
                return $region->getLevel()->getBlockAt($x, $y, $z + 1)->isSolid();
            case 3:
                return $region->getLevel()->getBlockAt($x, $y, $z - 1)->isSolid();
            case 2:
                return $region->getLevel()->getBlockAt($x + 1, $y, $z)->isSolid();
            case 1:
                return $region->getLevel()->getBlockAt($x - 1, $y, $z)->isSolid();
        }
        return false;
    }

    public function neighborChanged(TileSource $region, int $x, int $y, int $z, int $newX, int $newY, int $newZ): void
    {
        if (!$this->canSurvive($region, $x, $y, $z)) {
            $region->getLevel()->useBreakOn($this);
        }
    }

    public function onScheduledUpdate(): void
    {
        $this->tick(Loader::getTileSource($this->getLevel()), $this->x,$this->y,$this->z,Loader::getRandom($this->getLevel()));
    }

    public function tick(TileSource $region, int $x, int $y, int $z, Random $random): void
    {
    }

    public function onPlace(TileSource $region, int $x, int $y, int $z): void
    {
        $region->scheduleBlockUpdate($x, $y, $z, $this->id, 0);
    }

    public function isSignalSource(): bool
    {
        return true;
    }

    public function addCollisionShapes(TileSource $region, int $x, int $y, int $z, AxisAlignedBB $aabb, $list): bool
    {
        return false;
    }
}
