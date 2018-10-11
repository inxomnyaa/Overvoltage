<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Flowable;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Bearing;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use xenialdan\Overvoltage\Loader;
use xenialdan\Overvoltage\TileSource;

class RepeaterTile extends Flowable implements RedstonePowerSource
{
    public $delaySettings = [1, 2, 3, 4];
    private $powered = false;

    public function __construct(int $id, int $meta = 0, ?string $name = null, int $itemId = null)
    {
        $this->powered = $id === self::POWERED_REPEATER;
        parent::__construct($id, $meta, $name, $itemId);
    }


    public function getBoundingBox(): ?AxisAlignedBB
    {
        return new AxisAlignedBB(0.0, 0.0, 0.0, 1.0, 0.125, 1.0);
    }

    public function onScheduledUpdate(): void
    {
        $this->tick(Loader::getTileSource($this->getLevel()), $this->x,$this->y,$this->z,Loader::getRandom($this->getLevel()));
    }

    public function tick(TileSource $region, int $x, int $y, int $z, Random $random): void
    {
        $data = $region->getData($x, $y, $z);
        $shouldBePowered = $this->isGettingPowered($region, $x, $y, $z, $data);
        if ($this->powered && !$shouldBePowered)
            $region->getLevel()->setBlock(new Vector3($x, $y, $z), BlockFactory::get(93, $data));
        else if (!$this->powered) {
            $region->getLevel()->setBlock(new Vector3($x, $y, $z), BlockFactory::get(94, $data));
            if (!$shouldBePowered) {
                $delay = ($data & 12) >> 2;
                $region->scheduleBlockUpdate($x, $y, $z, 94, $this->delaySettings[$delay] * 2);
            }
        }
    }

    public function canSurvive(TileSource $region, int $x, int $y, int $z): bool
    {
        return $region->getLevel()->getBlockAt($x, $y - 1, $z)->isSolid() || $region->isRedstonePlacementException($x, $y - 1, $z);
    }

    public function mayPlace(TileSource $region, int $x, int $y, int $z): bool
    {
        return $region->getLevel()->getBlockAt($x, $y, $z)->getId() == 0 && ($region->getLevel()->getBlockAt($x, $y - 1, $z)->isSolid() || $region->isRedstonePlacementException($x, $y - 1, $z));
    }

    public function getSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        return $this->getDirectSignal($region, $x, $y, $z, $side);
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        if (!$this->powered)
            return 0;

        $rot = $region->getData($x, $y, $z) & 3;
        if ($rot == 0 && $side == 3)
            return 15;
        if ($rot == 1 && $side == 4)
            return 15;
        if ($rot == 2 && $side == 2)
            return 15;
        return ($rot == 3 && $side == 5) ? 15 : 0;
    }

    public function onNearbyBlockChange(): void
    {
        $this->neighborChanged(Loader::getTileSource($this->getLevel()), $this->x, $this->y, $this->z, 0,0,0);
    }

    public function neighborChanged(TileSource $region, int $x, int $y, int $z, int $changedX, int $changedY, int $changedZ): void
    {
        if (!$this->canSurvive($region, $x, $y, $z)) {
            $this->getLevel()->useBreakOn($this);
            $region->scheduleBlockUpdate($x, $y, $z, $this->getId(), 0);
            return;
        }
        $data = $region->getData($x, $y, $z);
        $shouldBePowered = $this->isGettingPowered($region, $x, $y, $z, $data);
        $delay = ($data & 12) >> 2;
        if ($this->powered && !$shouldBePowered)
            $region->scheduleBlockUpdate($x, $y, $z, $this->getId(), $this->delaySettings[$delay] * 2);
        else if (!$this->powered && $shouldBePowered)
            $region->scheduleBlockUpdate($x, $y, $z, $this->getId(), $this->delaySettings[$delay] * 2);
    }

    public function isGettingPowered(TileSource $region, int $x, int $y, int $z, int $data): bool
    {
        $rot = $data & 3;

        switch ($rot) {
            case 0:
                if ($region->getLevel()->getBlockIdAt($x, $y, $z + 1) == 55 && $region->getData($x, $y, $z + 1) > 0)
                    return true;
                return $region->getIndirectPowerLevelTo($x, $y, $z + 1, 3) > 0;
            case 2:
                if ($region->getLevel()->getBlockIdAt($x, $y, $z - 1) == 55 && $region->getData($x, $y, $z - 1) > 0)
                    return true;
                return $region->getIndirectPowerLevelTo($x, $y, $z - 1, 2) > 0;
            case 3:
                if ($region->getLevel()->getBlockIdAt($x + 1, $y, $z) == 55 && $region->getData($x + 1, $y, $z) > 0)
                    return true;
                return $region->getIndirectPowerLevelTo($x + 1, $y, $z, 5) > 0;
            case 1:
                if ($region->getLevel()->getBlockIdAt($x - 1, $y, $z) == 55 && $region->getData($x - 1, $y, $z) > 0)
                    return true;
                return $region->getIndirectPowerLevelTo($x - 1, $y, $z, 4) > 0;
        }
        return false;
    }

    public function onActivate(Item $item, Player $player = null): bool
    {
        parent::onActivate($item, $player);
        $data = $this->getDamage();
        $delay = ($data & 12) >> 2;
        $delay = $delay + 1 << 2 & 12;
        $this->getLevel()->setBlock($this, BlockFactory::get($this->getId(), $delay | $data & 3));
        return true;
    }

    public function isSignalSource(): bool
    {
        return true;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        if ($player !== null) {
            $this->meta = /*Bearing::toFacing(*/$player->getDirection()/*)*/;
        }
        $this->getLevel()->setBlock($blockReplace, $this, true, true);
        $region = Loader::getTileSource($this->getLevel());
        if ($this->isGettingPowered($region, $this->x, $this->y, $this->z, $region->getData($this->x, $this->y, $this->z)))
            $region->scheduleBlockUpdate($this->x, $this->y, $this->z, $this->getId(), 1);

        $region->updateNeighborsAt($this->x + 1, $this->y, $this->z);
        $region->updateNeighborsAt($this->x - 1, $this->y, $this->z);
        $region->updateNeighborsAt($this->x, $this->y, $this->z + 1);
        $region->updateNeighborsAt($this->x, $this->y, $this->z - 1);
        $region->updateNeighborsAt($this->x, $this->y - 1, $this->z);
        $region->updateNeighborsAt($this->x, $this->y + 1, $this->z);
        return true;
    }
    /*

     public function animateTick(TileSource $region, int $x, int $y, int $z, Random):void {
        if($powered) {
            int data = region->getData($x, $y, $z);
            int rot = data & 3;
            float posX = (x + 0.5) + (Mth::random($) - 0.5) * 0.2;
            float posY = (y + 0.4) + (Mth::random($) - 0.5) * 0.2;
            float posZ = (z + 0.5) + (Mth::random($) - 0.5) * 0.2;
            float offsetX = 0.0;
            float offsetZ = 0.0;
            int setting = (data & 12) >> 2;

            switch($rot) {
            case 0:
                offsetZ = torchOffset[$setting];
                break;
            case 1:
                offsetX = -torchOffset[$setting];
                break;
            case 2:
                offsetZ = -torchOffset[$setting];
                break;
            case 3:
                offsetX = torchOffset[$setting];
            }

            region->getLevel($)->addParticle(ParticleType::RedDust, {posX + $offsetX, $posY, posZ + offsetZ}, {0.0, 0.0, 0.0}, 1);
        }*/
}
