<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Transparent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Bearing;
use pocketmine\Player;
use xenialdan\Overvoltage\TileSource;

class PistonArmTile extends Transparent
{

    public function onBreak(Item $item, Player $player = null): bool
    {
        $data = $this->getDamage();
        $side = Bearing::fromFacing($this->getRotation($data));
        $tile = $this->getSide($side);
        if ($tile->getId() == Block::PISTON || $tile->getId() == Block::STICKY_PISTON) {
            $data = $tile->getDamage();
            if (PistonBaseTile::isPowered($data)) {
                $player->getLevel()->setBlock($tile, BlockFactory::get(0));
            }
        }
        return parent::onBreak($item, $player);
    }

    public function getVisualShape(TileSource $region, int $x, int $y, int $z, AxisAlignedBB $shape, bool $b): AxisAlignedBB
    {
        switch (getRotation($region->getData($x, $y, $z))) {
            case 0:
                $shape->setBounds(0.0, 0.0, 0.0, 1.0, 0.25, 1.0);
                break;
            case 1:
                $shape->setBounds(0.0, 0.75, 0.0, 1.0, 1.0, 1.0);
                break;
            case 2:
                $shape->setBounds(0.0, 0.0, 0.0, 1.0, 1.0, 0.25);
                break;
            case 3:
                $shape->setBounds(0.0, 0.0, 0.75, 1.0, 1.0, 1.0);
                break;
            case 4:
                $shape->setBounds(0.0, 0.0, 0.0, 0.25, 1.0, 1.0);
                break;
            case 5:
                $shape->setBounds(0.75, 0.0, 0.0, 1.0, 1.0, 1.0);
                break;
        }

        return $shape;
    }


    public function onNearbyBlockChange(): void
    {
        $rotation = $this->getRotation($this->getDamage());
        $tile = $this->getSide(Bearing::fromFacing($rotation));//TODO CHECK THIS!! CRITICAL, IMPORTANT!
        if ($tile->getId() != Block::PISTON && $tile != Block::STICKY_PISTON)
            $this->getLevel()->setBlock($this, BlockFactory::get(Block::AIR));
        else {
            //schedule update on the other end of the piston
            //$tile->neighborChanged($region, x - Facing::STEP_X[$rotation], y - Facing::STEP_Y[$rotation], z - Facing::STEP_Z[$rotation], $xx, $yy, $zz);
            $this->getLevel()->scheduleNeighbourBlockUpdates($tile);
        }
    }


    public function getRotation(int $data): int
    {
        return $data & 7;
    }

    public function isSticky(int $data): bool
    {
        $sticky = $data & 8;
        if ($sticky == 8) return true;
        if ($sticky == 0) return false;
        return false;
    }

    protected function recalculateCollisionBoxes(): array
    {
        $bbs = [];

        $data = $this->getDamage();
        /*$var9 = 0.25;
        $var10 = 0.375;
        $var11 = 0.625;
        $var12 = 0.25;
        $var13 = 0.75;*/

        switch (getRotation($data)) {
            case 0:
                $bbs[] = new AxisAlignedBB(0.0, 0.0, 0.0, 1.0, 0.25, 1.0);
                $bbs[] = new AxisAlignedBB(0.375, 0.25, 0.375, 0.625, 1.0, 0.625);
                break;
            case 1:
                $bbs[] = new AxisAlignedBB(0.0, 0.75, 0.0, 1.0, 1.0, 1.0);
                $bbs[] = new AxisAlignedBB(0.375, 0.0, 0.375, 0.625, 0.75, 0.625);
                break;
            case 2:
                $bbs[] = new AxisAlignedBB(0.0, 0.0, 0.0, 1.0, 1.0, 0.25);
                $bbs[] = new AxisAlignedBB(0.25, 0.375, 0.25, 0.75, 0.625, 1.0);
                break;
            case 3:
                $bbs[] = new AxisAlignedBB(0.0, 0.0, 0.75, 1.0, 1.0, 1.0);
                $bbs[] = new AxisAlignedBB(0.25, 0.375, 0.0, 0.75, 0.625, 0.75);
                break;
            case 4:
                $bbs[] = new AxisAlignedBB(0.0, 0.0, 0.0, 0.25, 1.0, 1.0);
                $bbs[] = new AxisAlignedBB(0.375, 0.25, 0.25, 0.625, 0.75, 1.0);
                break;
            case 5:
                $bbs[] = new AxisAlignedBB(0.75, 0.0, 0.0, 1.0, 1.0, 1.0);
                $bbs[] = new AxisAlignedBB(0.0, 0.375, 0.25, 0.75, 0.625, 0.75);
                break;
        }

        return $bbs;
    }

}
