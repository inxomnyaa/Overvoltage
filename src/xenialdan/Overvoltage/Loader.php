<?php

declare(strict_types=1);

namespace xenialdan\Overvoltage;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Random;
use xenialdan\Overvoltage\block\ButtonTile;
use xenialdan\Overvoltage\block\DoorTile;
use xenialdan\Overvoltage\block\FenceGateTile;
use xenialdan\Overvoltage\block\HeavyPressurePlateTile;
use xenialdan\Overvoltage\block\LampTile;
use xenialdan\Overvoltage\block\LeverTile;
use xenialdan\Overvoltage\block\LightPressurePlateTile;
use xenialdan\Overvoltage\block\NotGateTile;
use xenialdan\Overvoltage\block\PressurePlateTile;
use xenialdan\Overvoltage\block\RedstoneBlockTile;
use xenialdan\Overvoltage\block\RedstoneWireTile;
use xenialdan\Overvoltage\block\RepeaterTile;
use xenialdan\Overvoltage\block\TNT;
use xenialdan\Overvoltage\item\Repeater;

class Loader extends PluginBase implements Listener
{
    /** @var Loader */
    private static $instance = null;
    public static $tileSources = [];
    public static $random = [];
    const CAULDRON = "Cauldron";
    const HOPPER = "Hopper";
    const DISPENSER = "Dispenser";
    const DROPPER = "Dropper";
    const DAY_LIGHT_DETECTOR = "DLDetector";
    const NOTEBLOCK = "Music";
    const PISTON = "Piston";

    /**
     * Returns an instance of the plugin
     * @return Loader
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    public function onLoad()
    {
        self::$instance = $this;
        $this->initBlocks();
        $this->initItems();
        $this->initCreativeItems();
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    private function initBlocks(): void
    {
        BlockFactory::registerBlock(new RedstoneWireTile(55), true);
        BlockFactory::registerBlock(new NotGateTile(75), true);
        BlockFactory::registerBlock(new NotGateTile(76), true);
        BlockFactory::registerBlock(new LightPressurePlateTile(70, LightPressurePlateTile::MOBS), true);//stone plate
        BlockFactory::registerBlock(new LightPressurePlateTile(Block::LIGHT_WEIGHTED_PRESSURE_PLATE, LightPressurePlateTile::EVERYTHING), true);//gold plate
        BlockFactory::registerBlock(new LeverTile(), true);
        BlockFactory::registerBlock(new LampTile(Block::REDSTONE_LAMP), true);
        BlockFactory::registerBlock(new LampTile(Block::LIT_REDSTONE_LAMP), true);
        BlockFactory::registerBlock(new ButtonTile(Block::STONE_BUTTON), true);
        BlockFactory::registerBlock(new ButtonTile(Block::WOODEN_BUTTON), true);
        BlockFactory::registerBlock(new FenceGateTile(Block::FENCE_GATE), true);
        BlockFactory::registerBlock(new FenceGateTile(Block::SPRUCE_FENCE_GATE), true);
        BlockFactory::registerBlock(new FenceGateTile(Block::BIRCH_FENCE_GATE), true);
        BlockFactory::registerBlock(new FenceGateTile(Block::JUNGLE_FENCE_GATE), true);
        BlockFactory::registerBlock(new FenceGateTile(Block::DARK_OAK_FENCE_GATE), true);
        BlockFactory::registerBlock(new FenceGateTile(Block::ACACIA_FENCE_GATE), true);
        BlockFactory::registerBlock(new DoorTile(Block::OAK_DOOR_BLOCK), true);
        BlockFactory::registerBlock(new DoorTile(Block::SPRUCE_DOOR_BLOCK), true);
        BlockFactory::registerBlock(new DoorTile(Block::BIRCH_DOOR_BLOCK), true);
        BlockFactory::registerBlock(new DoorTile(Block::JUNGLE_DOOR_BLOCK), true);
        BlockFactory::registerBlock(new DoorTile(Block::ACACIA_DOOR_BLOCK), true);
        BlockFactory::registerBlock(new DoorTile(Block::DARK_OAK_DOOR_BLOCK), true);
        BlockFactory::registerBlock(new HeavyPressurePlateTile(147, 15), true);
        BlockFactory::registerBlock(new HeavyPressurePlateTile(148, 150), true);
        BlockFactory::registerBlock(new PressurePlateTile(Block::WOODEN_PRESSURE_PLATE, LightPressurePlateTile::EVERYTHING), true);
        BlockFactory::registerBlock(new RepeaterTile(Block::UNPOWERED_REPEATER), true);
        BlockFactory::registerBlock(new RepeaterTile(Block::POWERED_REPEATER), true);
        #BlockFactory::registerBlock(new PistonBaseTile(33, false), true);
        #BlockFactory::registerBlock(new PistonBaseTile(29, true), true);
        #BlockFactory::registerBlock(new PistonArmTile(34), true);
        #BlockFactory::registerBlock(new PistonMovingTile(36), true);
        BlockFactory::registerBlock(new RedstoneBlockTile(Block::REDSTONE_BLOCK), true);
        BlockFactory::registerBlock(new TNT(), true);
    }

    private function initItems(): void
    {
        ItemFactory::registerItem(new Repeater(100), true);
    }

    private function initCreativeItems(): void
    {
        Item::addCreativeItem(Item::get(Item::IRON_DOOR));
        Item::addCreativeItem(Item::get(Item::REPEATER));
        Item::addCreativeItem(Item::get(76));
        Item::addCreativeItem(Item::get(70));
        Item::addCreativeItem(Item::get(72));
        Item::addCreativeItem(Item::get(147));
        Item::addCreativeItem(Item::get(148));
        Item::addCreativeItem(Item::get(69));
        Item::addCreativeItem(Item::get(77));
        Item::addCreativeItem(Item::get(143));
        Item::addCreativeItem(Item::get(123));
        Item::addCreativeItem(Item::get(33));
        Item::addCreativeItem(Item::get(29));
        Item::initCreativeItems();
    }

    public function onLevelLoad(LevelLoadEvent $event)
    {
        if (!array_key_exists($event->getLevel()->getId(), self::$tileSources)) self::$tileSources[$event->getLevel()->getId()] = new TileSource($event->getLevel());
        if (!array_key_exists($event->getLevel()->getId(), self::$random)) self::$random[$event->getLevel()->getId()] = new Random($event->getLevel()->getSeed());
    }

    /**
     * @param Level $level
     * @return TileSource|null
     */
    public static function getTileSource(Level $level): ?TileSource
    {
        if (!array_key_exists($level->getId(), self::$tileSources)) self::$tileSources[$level->getId()] = new TileSource($level);
        return self::$tileSources[$level->getId()] ?? null;
    }

    public static function getRandom(Level $level): Random
    {
        if (!array_key_exists($level->getId(), self::$random)) self::$random[$level->getId()] = new Random($level->getSeed());
        return self::$random[$level->getId()] ?? null;
    }

}
/*
Button.php
Door.php
FenceGate.php
HeavyPressurePlate.php
Lamp.php
Lever.php
LightPressurePlate.php
NotGate.php
PistonArm.php
PistonBase.php
PistonMoving.php
PistonPushInfo.h
PressurePlate.php
RedstoneBlock.php
RedstoneColors.h
RedstoneWire.php
Repeater.php
Tile.php
TrapDoor.php
*/