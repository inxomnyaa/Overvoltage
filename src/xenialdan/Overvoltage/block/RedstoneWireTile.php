<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Flowable;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Tile;
use xenialdan\Overvoltage\Loader;
use xenialdan\Overvoltage\TileSource;

class RedstoneWireTile extends Flowable implements RedstoneComponent
{
    private $wiresProvidePower;

    public function getAxisAlignedBB()
    {
        return new AxisAlignedBB(0.0, 0.0, 0.0, 1.0, 0.0625, 1.0);
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        $region = Loader::getTileSource($this->getLevel());
        $x = $this->x;
        $y = $this->y;
        $z = $this->z;
        if(!$this->mayPlace($region, $x, $y, $z)) return false;
        parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
        $this->recalculate($region, $x, $y, $z);
        $region->updateNeighborsAt($x, $y + 1, $z);
        $region->updateNeighborsAt($x, $y - 1, $z);
        $this->updateWires($region, $x - 1, $y, $z);
        $this->updateWires($region, $x + 1, $y, $z);
        $this->updateWires($region, $x, $y, $z - 1);
        $this->updateWires($region, $x, $y, $z + 1);

        if ($region->getLevel()->getBlockAt($x - 1, $y, $z)->isSolid())
            $this->updateWires($region, $x - 1, $y + 1, $z);
        else
            $this->updateWires($region, $x - 1, $y - 1, $z);

        if ($region->getLevel()->getBlockAt($x + 1, $y, $z)->isSolid())
            $this->updateWires($region, $x + 1, $y + 1, $z);
        else
            $this->updateWires($region, $x + 1, $y - 1, $z);

        if ($region->getLevel()->getBlockAt($x, $y, $z - 1)->isSolid())
            $this->updateWires($region, $x, $y + 1, $z - 1);
        else
            $this->updateWires($region, $x, $y - 1, $z - 1);

        if ($region->getLevel()->getBlockAt($x, $y, $z + 1)->isSolid())
            $this->updateWires($region, $x, $y + 1, $z + 1);
        else
            $this->updateWires($region, $x, $y - 1, $z + 1);
        return true;
    }


    public function onBreak(Item $item, Player $player = null): bool
    {
        $region = Loader::getTileSource($this->getLevel());
        $x = $this->x;
        $y = $this->y;
        $z = $this->z;
        $region->updateNeighborsAt($x, $y + 1, $z);
        $region->updateNeighborsAt($x, $y - 1, $z);
        $region->updateNeighborsAt($x + 1, $y, $z);
        $region->updateNeighborsAt($x - 1, $y, $z);
        $region->updateNeighborsAt($x, $y, $z + 1);
        $region->updateNeighborsAt($x, $y, $z - 1);
        $this->recalculate($region, $x, $y, $z);
        $this->updateWires($region, $x - 1, $y, $z);
        $this->updateWires($region, $x + 1, $y, $z);
        $this->updateWires($region, $x, $y, $z - 1);
        $this->updateWires($region, $x, $y, $z + 1);

        if ($region->getLevel()->getBlockAt($x - 1, $y, $z)->isSolid())
            $this->updateWires($region, $x - 1, $y + 1, $z);
        else
            $this->updateWires($region, $x - 1, $y - 1, $z);

        if ($region->getLevel()->getBlockAt($x + 1, $y, $z)->isSolid())
            $this->updateWires($region, $x + 1, $y + 1, $z);
        else
            $this->updateWires($region, $x + 1, $y - 1, $z);

        if ($region->getLevel()->getBlockAt($x, $y, $z - 1)->isSolid())
            $this->updateWires($region, $x, $y + 1, $z - 1);
        else
            $this->updateWires($region, $x, $y - 1, $z - 1);

        if ($region->getLevel()->getBlockAt($x, $y, $z + 1)->isSolid())
            $this->updateWires($region, $x, $y + 1, $z + 1);
        else
            $this->updateWires($region, $x, $y - 1, $z + 1);

        return parent::onBreak($item, $player);
    }

    public function canSurvive(TileSource $region, int $x, int $y, int $z): bool
    {
        return $region->getLevel()->getBlockAt($x, $y - 1, $z)->isSolid() || $region->isRedstonePlacementException($x, $y - 1, $z);
    }

    public function mayPlace(TileSource $region, int $x, int $y, int $z): bool
    {
        return $region->getLevel()->getBlockIdAt($x, $y, $z) == 0 && ($region->getLevel()->getBlockAt($x, $y - 1, $z)->isSolid() || $region->isRedstonePlacementException($x, $y - 1, $z));
    }

    public function onNearbyBlockChange(): void
    {
        if (!$this->canSurvive(($region = Loader::getTileSource($this->getLevel())), $this->x, $this->y, $this->z)) {
            $region->getLevel()->useBreakOn($this);
            return;
        }
        $this->recalculate($region, $this->x, $this->y, $this->z);
    }

    public function isSignalSource(): bool
    {
        return $this->wiresProvidePower;
    }

    public function getSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        if (!$this->wiresProvidePower) return 0;
        return $this->getDirectSignal($region, $x, $y, $z, $side);
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        if (!$this->wiresProvidePower) return 0;
        $data = $region->getData($x, $y, $z);
        if ($data == 0) return 0;
        if ($side == 1) return $data;
        $xm = $this->canRedstoneConnectTo($region, $x - 1, $y, $z, 1) || !$region->getLevel()->getBlockAt($x - 1, $y, $z)->isSolid() && $this->canRedstoneConnectTo($region, $x - 1, $y - 1, $z, -1);
        $xp = $this->canRedstoneConnectTo($region, $x + 1, $y, $z, 3) || !$region->getLevel()->getBlockAt($x + 1, $y, $z)->isSolid() && $this->canRedstoneConnectTo($region, $x + 1, $y - 1, $z, -1);
        $zm = $this->canRedstoneConnectTo($region, $x, $y, $z - 1, 2) || !$region->getLevel()->getBlockAt($x, $y, $z - 1)->isSolid() && $this->canRedstoneConnectTo($region, $x, $y - 1, $z - 1, -1);
        $zp = $this->canRedstoneConnectTo($region, $x, $y, $z + 1, 0) || !$region->getLevel()->getBlockAt($x, $y, $z + 1)->isSolid() && $this->canRedstoneConnectTo($region, $x, $y - 1, $z + 1, -1);

        if (!$region->getLevel()->getBlockAt($x, $y + 1, $z)->isSolid()) {
            if ($region->getLevel()->getBlockAt($x - 1, $y, $z)->isSolid() && $this->canRedstoneConnectTo($region, $x - 1, $y + 1, $z, -1))
                $xm = true;
            if ($region->getLevel()->getBlockAt($x + 1, $y, $z)->isSolid() && $this->canRedstoneConnectTo($region, $x + 1, $y + 1, $z, -1))
                $xp = true;
            if ($region->getLevel()->getBlockAt($x, $y, $z - 1)->isSolid() && $this->canRedstoneConnectTo($region, $x, $y + 1, $z - 1, -1))
                $zm = true;
            if ($region->getLevel()->getBlockAt($x, $y, $z + 1)->isSolid() && $this->canRedstoneConnectTo($region, $x, $y + 1, $z + 1, -1))
                $zp = true;
        }

        if (!$zm && !$xp && !$xm && !$zp && $side >= 2 && $side <= 5)
            return $data;
        if ($side == 2 && $zm && !$xm && !$xp)
            return $data;
        if ($side == 3 && $zp && !$xm && !$xp)
            return $data;
        if ($side == 4 && $xm && !$zm && !$zp)
            return $data;
        if ($side == 5 && $xp && !$zm && !$zp)
            return $data;
        return 0;
    }

    public function addCollisionShapes(TileSource $region, int $x, int $y, int $z, AxisAlignedBB $aabb, $pool): bool
    {
        return false;
    }

    public function calculateChanges(TileSource $region, int $x, int $y, int $z, int $xx, int $yy, int $zz): void
    {
        $oldPower = $region->getData($x, $y, $z);
        $newPower = $this->getStrongerSignal($region, $xx, $yy, $zz, 0);
        $this->wiresProvidePower = false;
        $receivedPower = $region->getStrongestIndirectPower($x, $y, $z);
        $this->wiresProvidePower = true;

        if ($receivedPower > 0 && $receivedPower > $newPower - 1)
            $newPower = $receivedPower;

        $temp = 0;

        for ($it = 0; $it < 4; ++$it) {
            $newX = $x;
            $newZ = $z;

            if ($it == 0)
                $newX = $x - 1;
            if ($it == 1)
                ++$newX;
            if ($it == 2)
                $newZ = $z - 1;
            if ($it == 3)
                ++$newZ;

            if ($newX != $xx || $newZ != $zz)
                $temp = $this->getStrongerSignal($region, $newX, $y, $newZ, $temp);

            if ($region->getLevel()->getBlockAt($newX, $y, $newZ)->isSolid() && !$region->getLevel()->getBlockAt($x, $y + 1, $z)->isSolid()) {
                if (($newX != $xx || $newZ != $zz) && $y >= $yy)
                    $temp = $this->getStrongerSignal($region, $newX, $y + 1, $newZ, $temp);
            } else if (!$region->getLevel()->getBlockAt($newX, $y, $newZ)->isSolid() && ($newX != $xx || $newZ != $zz) && $y <= $yy)
                $temp = $this->getStrongerSignal($region, $newX, $y - 1, $newZ, $temp);
        }

        if ($temp > $newPower)
            $newPower = $temp - 1;
        else if ($newPower > 0)
            --$newPower;
        else
            $newPower = 0;

        if ($receivedPower > $newPower - 1)
            $newPower = $receivedPower;

        if ($oldPower != $newPower) {
            $region->getLevel()->setBlock(new Vector3($x, $y, $z), BlockFactory::get($this->getId(), $newPower));
            $region->updateNeighborsAt($x, $y, $z);
            $region->updateNeighborsAt($x - 1, $y, $z);
            $region->updateNeighborsAt($x + 1, $y, $z);
            $region->updateNeighborsAt($x, $y - 1, $z);
            $region->updateNeighborsAt($x, $y + 1, $z);
            $region->updateNeighborsAt($x, $y, $z - 1);
            $region->updateNeighborsAt($x, $y, $z + 1);
        }
    }

    public function recalculate(TileSource $region, int $x, int $y, int $z): void
    {
        $this->calculateChanges($region, $x, $y, $z, $x, $y, $z);
    }

    public function getStrongerSignal(TileSource $region, int $x, int $y, int $z, int $signal): int
    {
        if ($region->getLevel()->getBlockIdAt($x, $y, $z) != $this->getId()) return $signal;
        $signal2 = $region->getData($x, $y, $z);
        return ($signal2 > $signal) ? $signal2 : $signal;
    }

    public function updateWires(TileSource $region, int $x, int $y, int $z): void
    {
        if ($region->getLevel()->getBlockIdAt($x, $y, $z) != $this->getId()) return;
        $region->updateNeighborsAt($x, $y, $z);
        $region->updateNeighborsAt($x - 1, $y, $z);
        $region->updateNeighborsAt($x + 1, $y, $z);
        $region->updateNeighborsAt($x, $y, $z - 1);
        $region->updateNeighborsAt($x, $y, $z + 1);
        $region->updateNeighborsAt($x, $y - 1, $z);
        $region->updateNeighborsAt($x, $y + 1, $z);
    }

    public function canRedstoneConnectTo(TileSource $region, int $x, int $y, int $z, int $side): bool
    {
        $id = $this->getLevel()->getBlockAt($x, $y, $z)->getId();
        if ($id == $this->getId())
            return true;
        else if ($id == 0)
            return false;
        $block = BlockFactory::get($id);
        if (method_exists($block, 'isSignalSource'))
            return $block->isSignalSource() && $side != -1;
        return false;
    }
}