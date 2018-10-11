<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Transparent;
use pocketmine\level\Position;
use pocketmine\tile\Tile;

class PistonMovingTile extends Transparent
{

    private $pushedTile;
    private $pushedData;
    private $rotation;
    private $isExtending;
    private $renderHead;
    private $pos;

    public function newTileEntity(Position $position): Tile
    {
        return Tile::createTile("PistonMovingTile", null, Tile::createNBT(null));
        return new PistonTileEntity($pushedTile, $pushedData, $rotation, $isExtending, $renderHead, $position);
    }

    public function setTileEntityAttributes(Tile $pushedTile, int $pushedData, int $rotation, bool $isExtending, bool $renderHead, Position $pos): void
    {
        $this->pushedTile = $pushedTile;
        $this->pushedData = $pushedData;
        $this->rotation = $rotation;
        $this->isExtending = $isExtending;
        $this->renderHead = $renderHead;
        $this->pos = $pos;
    }
}