<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\RedstoneLamp;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use xenialdan\Overvoltage\Loader;
use xenialdan\Overvoltage\TileSource;

class LampTile extends RedstoneLamp implements RedstoneComponent
{

    public function __construct(int $id, int $meta = 0)
    {
        $this->id = $id;
        parent::__construct($meta);
    }


    public function getLightLevel(): int
    {
        return $this->isLit() ? 15 : 0;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {

        if ($this->isLit() && !Loader::getTileSource($this->getLevel())->isBlockIndirectlyGettingPowered($this->x, $this->y, $this->z))
            $this->getLevel()->scheduleDelayedBlockUpdate($this, /*4*/0);
        else if (!$this->isLit() && Loader::getTileSource($this->getLevel())->isBlockIndirectlyGettingPowered($this->x, $this->y, $this->z))
            $this->getLevel()->setBlock($this, BlockFactory::get(Block::LIT_REDSTONE_LAMP));

        return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    public function onNearbyBlockChange(): void
    {
        parent::onNearbyBlockChange();
        if ($this->isLit() && !Loader::getTileSource($this->getLevel())->isBlockIndirectlyGettingPowered($this->x, $this->y, $this->z))
            Loader::getTileSource($this->getLevel())->scheduleBlockUpdate($this->x, $this->y, $this->z, $this->id, /*4*/0);
        else if (!$this->isLit() && Loader::getTileSource($this->getLevel())->isBlockIndirectlyGettingPowered($this->x, $this->y, $this->z))
            $this->getLevel()->setBlock($this, BlockFactory::get(Block::LIT_REDSTONE_LAMP));
    }

    public function onScheduledUpdate(): void
    {
        $this->tick(Loader::getTileSource($this->getLevel()), $this->x,$this->y,$this->z,Loader::getRandom($this->getLevel()));
    }

    public function tick(TileSource $region, int $x, int $y, int $z, Random $random): void
    {
        if ($this->isLit() && !$region->isBlockIndirectlyGettingPowered($x, $y, $z))
            $region->getLevel()->setBlock(new Vector3($x, $y, $z), BlockFactory::get(Block::REDSTONE_LAMP));
    }

    public function isLit(): bool
    {
        return $this->getId() == Block::LIT_REDSTONE_LAMP;
    }

    public function getResource(Random $random, int $data, int $i): int
    {
        return Block::REDSTONE_LAMP;
    }
}
