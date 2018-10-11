<?php


namespace xenialdan\Overvoltage\block;


use xenialdan\Overvoltage\TileSource;

interface RedstonePowerSource
{

    public function getDirectSignal(TileSource $region, int $x, int $y, int $z, int $side);

}