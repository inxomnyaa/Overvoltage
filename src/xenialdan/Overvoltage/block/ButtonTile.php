<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Button;
use pocketmine\block\Solid;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\sound\ClickSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use xenialdan\Overvoltage\Loader;
use xenialdan\Overvoltage\TileSource;

class ButtonTile extends Button implements RedstonePowerSource
{

    public function __construct(int $id, int $meta = 0)
    {
        $this->id = $id;
        parent::__construct($meta);
    }

    public function getTickDelay(): int
    {
        return ($this->isWood()) ? 30 : 20;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        //$success = parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
        $this->meta = $face;
        if(!$this->mayPlace(Loader::getTileSource($this->getLevel()), $this->x,$this->y,$this->z, $face)) return false;
        $this->level->setBlock($this, $this, true, true);

        Loader::getTileSource($this->getLevel())->scheduleBlockUpdate($this->x, $this->y, $this->z, $this->getId(), 0);
        return true;
    }

    public function entityInside(TileSource $region, int $x, int $y, int $z, Entity $entity): bool
    {
        //if(this->id == 143 && (ts->getData($x, $y, $z) & 8) == 0) toggleIfArrowInside($ts, $x, $y, $z);
        return false;
    }

    public function listIncludesArrow($list): bool
    {
        for ($i = 0; $i < count($list); $i++) {
            //__android_log_print(ANDROID_LOG_INFO, "REDSTONE", "Arrow: %d", list[$i]->isInstanceOf(80));
            //if(list[$i]->getEntityTypeId() == 80) return true;
        }
        return false;
    }

    public function toggleIfArrowInside(TileSource $ts, int $x, int $y, int $z): void
    {
        /*EntityList list = ts->getEntities($NULL, AxisAlignedBB({x, $y, z}, {x + 1, y + 1, z + 1}));
        this->listIncludesArrow($list);
        bool flag = false; //(ts->getData($x, $y, $z) & 8) == 1;
        bool flag1 = false;
        EntityList list = ts->getEntities($NULL, AxisAlignedBB({x, $y, z}, {x + 1, y + 1, z + 1}));
        if(ts->getTile($x, $y, $z).id == 143 && list.size() > 0 && !this->listIncludesArrow($list)) flag1 = true;
        if(flag1 && !flag) {
            ts->setTileAndData($x, y + 1, $z, 3, 1, 3);
            ts->getLevel()->playSound(x + 0.5, y + 0.5, z + 0.5, "random.click", 0.3, 0.6);
        }
        */
    }

    public function getVisualShape(int $data, AxisAlignedBB $AxisAlignedBB, bool $b): AxisAlignedBB
    {
        $f = 0.1875;
        $f1 = 0.125;
        $f2 = 0.125;
        $AxisAlignedBB->setBounds(0.5 - $f, 0.5 - $f1, 0.5 - $f2, 0.5 + $f, 0.5 + $f1, 0.5 + $f2);
        return $AxisAlignedBB;
    }

    public function getVisualShape2(TileSource $region, int $x, int $y, int $z, AxisAlignedBB $AxisAlignedBB, bool $b): AxisAlignedBB
    {
        $data = $region->getBlockDataAt($x, $y, $z);
        $rot = $data & 7;
        $powered = ($data & 8) > 0;
        $f = 0.375;
        $f1 = 0.625;
        $f2 = 0.1875;
        $f3 = 0.125;
        if ($powered) $f3 = 0.0625;
        if ($rot == 1)
            $AxisAlignedBB->setBounds(0.0, $f, 0.5 - $f2, $f3, $f1, 0.5 + $f2);
        else if ($rot == 2)
            $AxisAlignedBB->setBounds(1.0 - $f3, $f, 0.5 - $f2, 1.0, $f1, 0.5 + $f2);
        else if ($rot == 3)
            $AxisAlignedBB->setBounds(0.5 - $f2, $f, 0.0, 0.5 + $f2, $f1, $f3);
        else if ($rot == 4)
            $AxisAlignedBB->setBounds(0.5 - $f2, $f, 1.0 - $f3, 0.5 + $f2, $f1, 1.0);
        else if ($rot == 5)
            $AxisAlignedBB->setBounds($f, 0.0, 0.5 - $f2, $f1, $f3, 0.5 + $f2);
        else if ($rot == 6)
            $AxisAlignedBB->setBounds($f, 1.0 - $f3, 0.5 - $f2, $f1, 1.0, 0.5 + $f2);

        return $AxisAlignedBB;
    }

    public function onNearbyBlockChange(): void
    {
        parent::onNearbyBlockChange();
        if (!$this->canSurvive(Loader::getTileSource($this->getLevel()), $this->x, $this->y, $this->z)) {
            $this->getLevel()->useBreakOn($this);
            $this->getLevel()->scheduleDelayedBlockUpdate($this, 0);
        }
    }

    public function onActivate(Item $item, Player $player = null): bool
    {
        $data = $this->getDamage();
        $rot = $data & 7;
        $power = 8 - ($data & 8);
        if ($power == 0) return true;
        $player->getLevel()->setBlock($this, BlockFactory::get($this->getId(), $rot + $power));
        $player->getLevel()->addSound(new ClickSound($this->add(0.5, 0.5, 0.5), mt_rand(0.3, 0.6)));
        $player->getLevel()->scheduleNeighbourBlockUpdates($this);
        if ($rot == 1)
            $player->getLevel()->scheduleNeighbourBlockUpdates($this->add(-1, 0));
        else if ($rot == 2)
            $player->getLevel()->scheduleNeighbourBlockUpdates($this->add(1));
        else if ($rot == 3)
            $player->getLevel()->scheduleNeighbourBlockUpdates($this->add(0, 0, -1));
        else if ($rot == 4)
            $player->getLevel()->scheduleNeighbourBlockUpdates($this->add(0, 0, 1));
        else if ($rot == 5)
            $player->getLevel()->scheduleNeighbourBlockUpdates($this->add(0, -1));
        else if ($rot == 6)
            $player->getLevel()->scheduleNeighbourBlockUpdates($this->add(0, 1));
        $player->getLevel()->scheduleDelayedBlockUpdate($this, $this->getTickDelay());
        return true;
    }

    public function onBreak(Item $item, Player $player = null): bool
    {
        $data = $this->getDamage();
        if (($data & 8) > 0) {
            $this->getLevel()->scheduleNeighbourBlockUpdates($this);

            switch ($data & 7) {
                case 6:
                    $this->getLevel()->scheduleNeighbourBlockUpdates($this->add(0, 1));
                    break;
                case 5:
                    $this->getLevel()->scheduleNeighbourBlockUpdates($this->add(0, -1));
                    break;
                case 4:
                    $this->getLevel()->scheduleNeighbourBlockUpdates($this->add(0, 0, 1));
                    break;
                case 3:
                    $this->getLevel()->scheduleNeighbourBlockUpdates($this->add(0, 0, -1));
                    break;
                case 2:
                    $this->getLevel()->scheduleNeighbourBlockUpdates($this->add(1));
                    break;
                case 1:
                    $this->getLevel()->scheduleNeighbourBlockUpdates($this->add(-1));
                    break;
            }
        }
        return parent::onBreak($item, $player);
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        return (($region->getBlockDataAt($x, $y, $z) & 8) > 0) ? 15 : 0;
    }

    public function getSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        $data = $region->getBlockDataAt($x, $y, $z);
        if (($data & 8) == 0) return 0;
        $rot = $data & 7;
        if ($rot == 6 && $side == 0)
            return 15;
        if ($rot == 5 && $side == 1)
            return 15;
        if ($rot == 4 && $side == 2)
            return 15;
        if ($rot == 3 && $side == 3)
            return 15;
        if ($rot == 2 && $side == 4)
            return 15;
        if ($rot == 1 && $side == 5)
            return 15;
        return 0;
    }

    public function isSignalSource(): bool
    {
        return true;
    }

    public function mayPlace(TileSource $region, int $x, int $y, int $z, int $side): bool
    {
        $block = $region->getLevel()->getBlockAt($x, $y, $z)->getSide(Facing::opposite($side));//TODO check if opposite or not!
        return $block->isSolid() || $region->isRedstonePlacementException($block->x, $block->y, $block->z);
        switch ($side) {
            case 2:
                return $region->getLevel()->getBlockAt($x, $y, $z + 1) instanceof Solid;
            case 3:
                return $region->getLevel()->getBlockAt($x, $y, $z - 1) instanceof Solid;
            case 4:
                return $region->getLevel()->getBlockAt($x + 1, $y, $z) instanceof Solid;
            case 5:
                return $region->getLevel()->getBlockAt($x - 1, $y, $z) instanceof Solid;
            case 1:
                return $region->getLevel()->getBlockAt($x, $y - 1, $z) instanceof Solid;
            case 0:
                return $region->getLevel()->getBlockAt($x, $y + 1, $z) instanceof Solid;
        }
        return false;
    }

    public function canSurvive(TileSource $region, int $x, int $y, int $z): bool
    {
        return true;//TODO remove
        $rot = $region->getBlockDataAt($x, $y, $z) & 7;
        switch ($rot) {
            case 6:
                return $region->getLevel()->getBlockAt($x, $y + 1, $z) instanceof Solid;
            case 5:
                return $region->getLevel()->getBlockAt($x, $y - 1, $z) instanceof Solid;
            case 4:
                return $region->getLevel()->getBlockAt($x, $y, $z + 1) instanceof Solid;
            case 3:
                return $region->getLevel()->getBlockAt($x, $y, $z - 1) instanceof Solid;
            case 2:
                return $region->getLevel()->getBlockAt($x + 1, $y, $z) instanceof Solid;
            case 1:
                return $region->getLevel()->getBlockAt($x - 1, $y, $z) instanceof Solid;
        }
        return false;
    }

    public function onScheduledUpdate(): void
    {
        $this->tick(Loader::getTileSource($this->getLevel()), $this->x,$this->y,$this->z,Loader::getRandom($this->getLevel()));
    }

    public function tick(TileSource $region, int $x, int $y, int $z, Random $random): void
    {
        $data = $region->getBlockDataAt($x, $y, $z);

        if (($data & 8) == 0) {
            if ($this->isWood()) $this->toggleIfArrowInside($region, $x, $y, $z);
            return;
        }

        $region->getLevel()->setBlock(new Vector3($x, $y, $z), new self($this->getId(), $data & 7));
        $region->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y, $z));
        $rot = $data & 7;
        if ($rot == 1)
            $region->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x - 1, $y, $z));
        else if ($rot == 2)
            $region->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x + 1, $y, $z));
        else if ($rot == 3)
            $region->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y, $z - 1));
        else if ($rot == 4)
            $region->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y, $z + 1));
        else if ($rot == 5)
            $region->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y - 1, $z));
        else if ($rot == 6)
            $region->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y + 1, $z));
        $region->getLevel()->addSound(new ClickSound($this->add(0.5, 0.5, 0.5), mt_rand(0.3, 0.5)));
    }

    public function isWood(): bool
    {
        return $this->id === 143;
    }

    public function hasEntityCollision(): bool
    {
        return true;
    }

    public function getCollisionBoxes(): array
    {
        return parent::getCollisionBoxes(); // TODO: Change the autogenerated stub
    }
}