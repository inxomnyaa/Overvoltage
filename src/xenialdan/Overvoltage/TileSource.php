<?php


namespace xenialdan\Overvoltage;


use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;

class TileSource
{

    private $tickQueue;
    private $levelId;

    public function __construct(Level $level)
    {
        $this->tickQueue = new \SplQueue();
        $this->levelId = $level->getId();
    }


    public function scheduleBlockUpdate(int $x, int $y, int $z, int $id, int $delay): void
    {
        $this->getLevel()->scheduleDelayedBlockUpdate(new Vector3($x, $y, $z), $delay);
    }

    public function isBlockIndirectlyGettingPowered(int $x, int $y, int $z): bool
    {
        if ($this->getIndirectPowerLevelTo($x, $y - 1, $z, 0) > 0) return true;
        if ($this->getIndirectPowerLevelTo($x, $y + 1, $z, 1) > 0) return true;
        if ($this->getIndirectPowerLevelTo($x, $y, $z - 1, 2) > 0) return true;
        if ($this->getIndirectPowerLevelTo($x, $y, $z + 1, 3) > 0) return true;
        if ($this->getIndirectPowerLevelTo($x - 1, $y, $z, 4) > 0) return true;
        return $this->getIndirectPowerLevelTo($x + 1, $y, $z, 5) > 0;
    }

    public function getIndirectPowerLevelTo(int $x, int $y, int $z, int $side): int
    {
        $block = $this->getLevel()->getBlockAt($x, $y, $z);
        if ($block->getId() == 0) return 0;
        if ($block->isSolid() && $block->getId() != Block::REDSTONE_BLOCK) return $this->getBlockPowerInput($x, $y, $z);
        else
            if (method_exists($block, 'getDirectSignal'))
                return $block->getDirectSignal($this, $x, $y, $z, $side);
        return 0;
    }

    public function getStrongestIndirectPower(int $x, int $y, int $z): int
    {
        $var4 = 0;

        for ($var5 = 0; $var5 < 6; ++$var5) {
            $sideBlock = $this->getLevel()->getBlockAt($x, $y, $z)->getSide($var5);
            $var6 = $this->getIndirectPowerLevelTo($sideBlock->x, $sideBlock->y, $sideBlock->z, $var5);

            if ($var6 >= 15)
                return 15;
            if ($var6 > $var4)
                $var4 = $var6;
        }
        return $var4;
    }

    public function isBlockProvidingPowerTo(int $x, int $y, int $z, int $side): int
    {
        $block = $this->getLevel()->getBlockAt($x, $y, $z);
        if ($block->getId() == 0) return 0;
        else if(method_exists($block, 'getSignal'))
            return $block->getSignal($this, $x, $y, $z, $side);
        return 0;
    }

    public function getIndirectPowerOutput(int $x, int $y, int $z, int $side): bool
    {
        return $this->getIndirectPowerLevelTo($x, $y, $z, $side) > 0;
    }

    public function isBlockGettingPowered(int $x, int $y, int $z): bool
    {
        if ($this->isBlockProvidingPowerTo($x, $y - 1, $z, 0)) return true;
        if ($this->isBlockProvidingPowerTo($x, $y + 1, $z, 1)) return true;
        if ($this->isBlockProvidingPowerTo($x, $y, $z - 1, 2)) return true;
        if ($this->isBlockProvidingPowerTo($x, $y, $z + 1, 3)) return true;
        if ($this->isBlockProvidingPowerTo($x - 1, $y, $z, 4)) return true;
        return $this->isBlockProvidingPowerTo($x + 1, $y, $z, 5);
    }

    public function getBlockPowerInput(int $x, int $y, int $z): int
    {

        $var4 = 0;
        $var5 = max($var4, $this->isBlockProvidingPowerTo($x, $y - 1, $z, 0));

        if ($var5 >= 15)
            return $var5;
        else {
            $var5 = max($var5, $this->isBlockProvidingPowerTo($x, $y + 1, $z, 1));

            if ($var5 >= 15)
                return $var5;
            else {
                $var5 = max($var5, $this->isBlockProvidingPowerTo($x, $y, $z - 1, 2));

                if ($var5 >= 15)
                    return $var5;
                else {
                    $var5 = max($var5, $this->isBlockProvidingPowerTo($x, $y, $z + 1, 3));

                    if ($var5 >= 15)
                        return $var5;
                    else {
                        $var5 = max($var5, $this->isBlockProvidingPowerTo($x - 1, $y, $z, 4));

                        if ($var5 >= 15)
                            return $var5;
                        else {
                            $var5 = max($var5, $this->isBlockProvidingPowerTo($x + 1, $y, $z, 5));
                            return $var5 >= 15 ? $var5 : $var5;
                        }
                    }
                }
            }
        }
    }

    public function isRedstonePlacementException(int $x, int $y, int $z): bool
    {
        $block = $this->getLevel()->getBlockAt($x, $y, $z);
        return ($block->getId() == 44 && ($block->getDamage() & 8) > 0) || ($block->getId() == 158 && ($block->getDamage() & 8) > 0) || $block->getId() == 89;
    }

    /**
     * @return null|Level
     */
    public function getLevel(): ?Level
    {
        return Server::getInstance()->getLevel($this->levelId);
    }

    /**
     * @return \SplQueue
     */
    public function getTickQueue(): \SplQueue
    {
        return $this->tickQueue;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $z
     * @return int
     */
    public function getBlockDataAt(int $x, int $y, int $z)
    {
        return $this->getLevel()->getBlockAt($x, $y, $z)->getDamage();
    }

    public function getData(int $x, int $y, int $z)
    {
        return $this->getLevel()->getBlockDataAt($x, $y, $z);
    }

    public function updateNeighborsAt(int $x, int $y, int $z)
    {
        $this->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y, $z));
    }

}