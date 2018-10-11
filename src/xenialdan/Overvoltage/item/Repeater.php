<?php


namespace xenialdan\Overvoltage\item;


use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use xenialdan\Overvoltage\block\RepeaterTile;

class Repeater extends Item
{

    public function __construct(int $id, int $meta = 0, string $name = "Repeater")
    {
        parent::__construct($id, $meta, $name);
    }
}