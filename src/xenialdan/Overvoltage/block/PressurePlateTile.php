<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Fence;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\sound\ClickSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use xenialdan\Overvoltage\Loader;
use xenialdan\Overvoltage\TileSource;

class PressurePlateTile extends LightPressurePlateTile
{

    public function getBoundingBox(): AxisAlignedBB
    {
        return new AxisAlignedBB(0.0625, 0.0, 0.0625, 1.0 - 0.0625, 0.0625, 1.0 - 0.0625);
    }

    public function onEntityCollide(Entity $entity): void
    {
        $this->entityInside(Loader::getTileSource($this->getLevel()), $this->x, $this->y,$this->z,$entity);
    }

    public function entityInside(TileSource $region, int $x, int $y, int $z, Entity $entity): bool
    {
        $power = $this->getPowerFromData($region->getLevel()->getBlockDataAt($x, $y, $z));
        if ($power == 0) $this->setStateIfMobInteractsWithPlate($region, $x, $y, $z, $power);
        return true;
    }

    public function mayPlace(TileSource $region, int $x, int $y, int $z): bool
    {
        $block = $region->getLevel()->getBlockAt($x, $y - 1, $z);
        return $block->isSolid() || $block->getId() == Block::GLOWSTONE || $block instanceof Fence;
    }

    public function onNearbyBlockChange(): void
    {
        parent::onNearbyBlockChange();
        if (!$this->mayPlace(Loader::getTileSource($this->getLevel()), $this->x, $this->y, $this->z)) {
            $this->getLevel()->useBreakOn($this);

            Loader::getTileSource($this->getLevel())->scheduleBlockUpdate($this->x, $this->y, $this->z, $this->id, 0);
        }
    }

    public function onScheduledUpdate(): void
    {
        $this->tick(Loader::getTileSource($this->getLevel()), $this->x,$this->y,$this->z,Loader::getRandom($this->getLevel()));
    }

    public function tick(TileSource $region, int $x, int $y, int $z, Random $random): void
    {
        $power = $this->getPowerFromData($region->getLevel()->getBlockDataAt($x, $y, $z));
        if ($power > 0) $this->setStateIfMobInteractsWithPlate($region, $x, $y, $z, $power);
    }

    public function getSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        return ($side == 1) ? $this->getPowerFromData($region->getData($x, $y, $z)) : 0;
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        return $this->getPowerFromData($region->getData($x, $y, $z));
    }

    public function isSignalSource(): bool
    {
        return true;
    }

    public function onBreak(Item $item, Player $player = null): bool
    {
        $region = Loader::getTileSource($this->getLevel());
        if ($this->getPowerFromData($region->getData($this->x, $this->y, $this->z)) > 0) {
            $region->updateNeighborsAt($this->x, $this->y, $this->z);
            $region->updateNeighborsAt($this->x, $this->y - 1, $this->z);
        }
        return parent::onBreak($item, $player);
    }

    public function setStateIfMobInteractsWithPlate(TileSource $region, int $x, int $y, int $z, int $power): void
    {
        $newPower = $this->getPower($region, $x, $y, $z);
        $shouldBePowered = $newPower > 0;
        $isPowered = $power > 0;

        if ($power != $newPower) {
            $region->getLevel()->setBlock(new Vector3($x, $y, $z), BlockFactory::get($this->getId(), $this->getDataFromPower($newPower)));
            $region->updateNeighborsAt($x, $y, $z);
            $region->updateNeighborsAt($x, $y - 1, $z);
        }

        if ($shouldBePowered && !$isPowered)
            $region->getLevel()->addSound(new ClickSound($this->add(0.5, 0.1, 0.5), mt_rand(0.3, 0.6)));
        if (!$shouldBePowered && $isPowered)
            $region->getLevel()->addSound(new ClickSound($this->add(0.5, 0.1, 0.5), mt_rand(0.3, 0.5)));

        if ($shouldBePowered)
            $region->scheduleBlockUpdate($x, $y, $z, $this->getId(), $this->getTickDelay());
    }

    public function onPlace(TileSource $region, int $x, int $y, int $z): void
    {
    }

    public function getTickDelay(): int
    {
        return 20;
    }

    public function getVisualShape(TileSource $region, int $x, int $y, int $z, AxisAlignedBB $aabb, bool $b): AxisAlignedBB
    {
        $aabb->setBounds(0.0625, 0.0, 0.0625, 1.0 - 0.0625, 0.0625, 1.0 - 0.0625);
        if ($this->getPowerFromData($region->getData($x, $y, $z)) == 0)
            $aabb->setBounds(0.0625, 0.0, 0.0625, 1.0 - 0.0625, 0.0625, 1.0 - 0.0625);
        else
            $aabb->setBounds(0.0625, 0.0, 0.0625, 1.0 - 0.0625, 0.03125, 1.0 - 0.0625);
        return $aabb;
    }

    public function addCollisionShapes(TileSource & $region, int $x, int $y, int $z, AxisAlignedBB $aabb, $aabbs): bool
    {
        return false;
    }
}
