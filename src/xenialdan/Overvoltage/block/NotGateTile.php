<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\RedstoneTorch;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use xenialdan\Overvoltage\Loader;
use xenialdan\Overvoltage\TileSource;

class NotGateTile extends RedstoneTorch implements RedstonePowerSource
{

    public function __construct(int $id, int $meta = 0)
    {
        $this->id = $id;
        parent::__construct($meta);
    }

    /*
     public function mayPlace(TileSource $region, int $x, int $y, int $z, int $side):bool {
        switch($side) {
        case 0:
            return false;
        case 1:
            return Tile::solid[region->getTile($x, y - 1, $z).id];
        case 2:
            return Tile::solid[region->getTile($x, $y, z + 1).id];
        case 3:
            return Tile::solid[region->getTile($x, $y, z - 1).id];
        case 4:
            return Tile::solid[region->getTile(x + 1, $y, $z).id];
        case 5:
            return Tile::solid[region->getTile(x - 1, $y, $z).id];
        }
    }

     public function canSurvive(TileSource $region, int $x, int $y, int $z):bool {
        int data = region->getData($x, $y, $z);
        switch($data) {
        case 5:
            return Tile::solid[region->getTile($x, y - 1, $z).id];
        case 3:
            return Tile::solid[region->getTile($x, $y, z - 1).id];
        case 4:
            return Tile::solid[region->getTile($x, $y, z + 1).id];
        case 1:
            return Tile::solid[region->getTile(x - 1, $y, $z).id];
        case 2:
            return Tile::solid[region->getTile(x + 1, $y, $z).id];
        }
        return true;
    }*/

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        if (!parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player)) return false;
        if ($this->isActive()) {
            $this->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($this->x, $this->y - 1, $this->z));
            $this->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($this->x, $this->y + 1, $this->z));
            $this->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($this->x - 1, $this->y, $this->z));
            $this->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($this->x + 1, $this->y, $this->z));
            $this->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($this->x, $this->y, $this->z - 1));
            $this->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($this->x, $this->y, $this->z + 1));
        }
        return true;
    }

    public function onBreak(Item $item, Player $player = null): bool
    {
        if ($this->isActive()) $this->getLevel()->scheduleDelayedBlockUpdate($this, 0);
        return parent::onBreak($item, $player);
    }

    public function isSignalSource(): bool
    {
        return $this->isActive();
    }

    public function getTickDelay(): int
    {
        return 2;
    }

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        if (!$this->isActive()) return 0;
        $data = $region->getLevel()->getBlockDataAt($x, $y, $z);
        if ($data == 5 && $side == 1) return 0;
        if ($data == 3 && $side == 3) return 0;
        if ($data == 4 && $side == 2) return 0;
        if ($data == 1 && $side == 5) return 0;
        if ($data == 2 && $side == 4) return 0;
        return 15;
    }

    public function getSignal(TileSource $region, int $x, int $y, int $z, int $side): int
    {
        if ($side == 0) return $this->getDirectSignal($region, $x, $y, $z, $side);
        return 0;
    }

    public function onNearbyBlockChange(): void
    {
        parent::onNearbyBlockChange();
        if ($this->getLevel()->getBlock($this)->getId() !== Block::AIR)
            $this->getLevel()->scheduleDelayedBlockUpdate($this, $this->getTickDelay());
    }

    public function onScheduledUpdate(): void
    {
        $this->tick(Loader::getTileSource($this->getLevel()), $this->x,$this->y,$this->z,Loader::getRandom($this->getLevel()));
    }

    public function tick(TileSource $region, int $x, int $y, int $z, Random $random): void
    {
        $burnt = $this->checkForBurnout($region, $x, $y, $z);
        if ($burnt && $region->getLevel()->getBlockIdAt($x, $y, $z) == Block::LIT_REDSTONE_TORCH) {
            $region->getLevel()->setBlock(new Vector3($x, $y, $z), BlockFactory::get(Block::UNLIT_REDSTONE_TORCH, $this->getDamage()));
            $this->getLevel()->scheduleNeighbourBlockUpdates(new Vector3($x, $y + 1, $z));
        } else if (!$burnt && $region->getLevel()->getBlockIdAt($x, $y, $z) == Block::UNLIT_REDSTONE_TORCH)
            $region->getLevel()->setBlock(new Vector3($x, $y, $z), BlockFactory::get(Block::LIT_REDSTONE_TORCH, $this->getDamage()));
    }

    public function isActive(): bool
    {
        return $this->getId() == Block::LIT_REDSTONE_TORCH;
    }

    public function checkForBurnout(TileSource $region, int $x, int $y, int $z): bool
    {
        $data = $region->getLevel()->getBlockDataAt($x, $y, $z);
        if ($data == 5 && $region->getIndirectPowerOutput($x, $y - 1, $z, 0)) return true;
        if ($data == 3 && $region->getIndirectPowerOutput($x, $y, $z - 1, 2)) return true;
        if ($data == 4 && $region->getIndirectPowerOutput($x, $y, $z + 1, 3)) return true;
        if ($data == 1 && $region->getIndirectPowerOutput($x - 1, $y, $z, 4)) return true;
        return $data == 2 && $region->getIndirectPowerOutput($x + 1, $y, $z, 5);
    }
}