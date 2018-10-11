<?php

namespace xenialdan\Overvoltage\block;

use pocketmine\block\Transparent;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;
use xenialdan\Overvoltage\TileSource;

class PistonTileEntity extends Tile
{
public function __construct(Level $level, CompoundTag $nbt)
{
    parent::__construct($level, $nbt);
    //Tile $, int $, int $, bool $, bool $, const TilePos& position) :
	//TileEntity(TileEntityType::Piston, $, "Piston") {

	$this->storedBlock = $;
	$this->storedData = $;
	$this->orientation = $;
	$this->extending = $;
	$this->renderHead = $;}
/**
 * Reads additional data from the CompoundTag on tile creation.
 *
 * @param CompoundTag $nbt
 */protected function readSaveData(CompoundTag $nbt): void
{
    // TODO: Implement readSaveData() method.
}/**
 * Writes additional save data to a CompoundTag, not including generic things like ID and coordinates.
 *
 * @param CompoundTag $nbt
 */protected function writeSaveData(CompoundTag $nbt): void
{
    // TODO: Implement writeSaveData() method.
}

 public function getInterpolatedProgress(float partialTicks):float {
	if(partialTicks > 1.0)
		partialTicks = 1.0;

	return oldProgress + (progress - oldProgress) * $;
}

 public function getOffsetX(float partialTicks):float {
	return extending ? (getInterpolatedProgress(partialTicks) - 1.0) * Facing::STEP_X[orientation] : (1.0 - getInterpolatedProgress(partialTicks)) * Facing::STEP_X[orientation];
}

 public function getOffsetY(float partialTicks):float {
	return extending ? (getInterpolatedProgress(partialTicks) - 1.0) * Facing::STEP_Y[orientation] : (1.0 - getInterpolatedProgress(partialTicks)) * Facing::STEP_Y[orientation];
}

 public function getOffsetZ(float partialTicks):float {
	return extending ? (getInterpolatedProgress(partialTicks) - 1.0) * Facing::STEP_Z[orientation] : (1.0 - getInterpolatedProgress(partialTicks)) * Facing::STEP_Z[orientation];
}

 public function pushEntitiesInside(float _progress, float diff):void {
	//if(extending) _progress = 1.0 - _progress;
	//else --_progress;

	//AABB storedTileBB = Tile::pistonExtension->getStoredTileBoundingBox(region, $, $, progress);

	//if(!storedTileBB.isEmpty()) {
	      
	//}
}

 public function placeTileAndFinish(TileSource region):void {
	if(isFinished())
		return;
	if(oldProgress < 1.0) {
		oldProgress = progress = 1.0;
		finish();

		if(region->getTile(pos.x, pos.y, pos.z).id == 36) {
			region->setTileAndData(pos.x, pos.y, pos.z, {storedBlock->id, storedData}, 3);
			region->updateNeighborsAt(pos, storedBlock->id);
		}
	}
}

 public function onRemoved():void {

}

 public function tick(TileSource region):void {
	//TileEntity::tick(region);
	//region->removeTile(pos.x, pos.y, pos.z/*, {storedBlock->id, storedData}, 3/);
	oldProgress = $;
		
	if(oldProgress >= 1.0) {
		pushEntitiesInside(1.0, 0.25);
		
		if(region->getTile(pos.x, pos.y, pos.z).id == 36) {
			if(storedBlock == NULL)
				region->removeTile(pos.x, pos.y, pos.z);
			else
				//region->setTileAndData(pos.x, pos.y, pos.z, {storedBlock->id, storedData}, 3);
			
			region->updateNeighborsAt(pos, storedBlock->id);
		}
	} else {
		progress += 0.5;

		if(progress > 1.0)
			progress = 1.0;

		if(extending)
			pushEntitiesInside(progress, progress - oldProgress + 0.0625);
	}
}

 public function isFinished():bool {
	return $;
}

 public function finish():void {
	finished = true;
}

 public function load(CompoundTag nbt):void {

}

 public function save(CompoundTag nbt):bool {

}}
