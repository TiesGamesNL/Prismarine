<?php

/**
 *
 *  ____       _                          _
 * |  _ \ _ __(_)___ _ __ ___   __ _ _ __(_)_ __   ___
 * | |_) | '__| / __| '_ ` _ \ / _` | '__| | '_ \ / _ \
 * |  __/| |  | \__ \ | | | | | (_| | |  | | | | |  __/
 * |_|   |_|  |_|___/_| |_| |_|\__,_|_|  |_|_| |_|\___|
 *
 * Prismarine is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Prismarine Team
 * @link   https://github.com/PrismarineMC/Prismarine
 *
 *
 */

namespace pocketmine\inventory;

use pocketmine\inventory\CustomInventory;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\inventory\InventoryType;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\NBT;
use pocketmine\network\protocol\BlockEntityDataPacket;
use pocketmine\inventory\InventoryHolder;

class WindowInventory extends CustomInventory{

    protected $name = "";
    protected $tile;
    protected $block;

    public function __construct(Player $player, $size = 27, $name = "") {
        $this->tile = Tile::CHEST;
        $this->block = 54;
        $type = InventoryType::get(InventoryType::CHEST);
        switch($size){
            case 5:
                 $this->tile = Tile::HOPPER;
                 $this->block = 154;
                 $type = InventoryType::get(InventoryType::HOPPER);
                 break;
             case 9:
                 $this->tile = Tile::DISPENSER;
                 $this->block = 23;
                 $type = InventoryType::get(InventoryType::DISPENSER);
                 break;
             case 27:
                 $type = InventoryType::get(InventoryType::CHEST);
             case 54:
                 $type = InventoryType::get(InventoryType::DOUBLE_CHEST);
                 $this->tile = Tile::CHEST;
                 $this->block = 54;
             default:
                 $who->getServer()->getLogger()->notice("Unknown window size. If must be one from: 5, 9, 27, 54. Using default size(27).");
        }
        $holder = new WindowHolder($player->getFloorX(), $player->getFloorY() - 3, $player->getFloorZ(), $this);
        parent::__construct($holder, $type);
    }

    public function onOpen(Player $who){
        $this->holder = $holder = new WindowHolder($who->getFloorX(), $who->getFloorY() - 3, $who->getFloorZ(), $this);
        $pk = new UpdateBlockPacket();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;
        $pk->blockId = $this->block;
        $pk->blockData = 0;
        $pk->flags = UpdateBlockPacket::FLAG_ALL;
        $who->dataPacket($pk);
        $c = new CompoundTag("", [
            new StringTag("id", $this->tile),
            new IntTag("x", (int) $holder->x),
            new IntTag("y", (int) $holder->y),
            new IntTag("z", (int) $holder->z)
        ]);
        if($this->name !== ""){
            $c->CustomName = new StringTag("CustomName", $this->name);
        }
        $nbt = new NBT(NBT::LITTLE_ENDIAN);
        $nbt->setData($c);
        $pk = new BlockEntityDataPacket();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;
        $pk->namedtag = $nbt->write();
        $who->dataPacket($pk);
        parent::onOpen($who);
        $this->sendContents($who);
    }

    public function onClose(Player $who){
        $holder = $this->holder;
        $pk = new UpdateBlockPacket();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;
        $pk->blockId = $who->getLevel()->getBlockIdAt($holder->x, $holder->y, $holder->z);
        $pk->blockData = $who->getLevel()->getBlockDataAt($holder->x, $holder->y, $holder->z);
        $pk->flags = UpdateBlockPacket::FLAG_ALL;
        $who->dataPacket($pk);
        parent::onClose($who);
    }
}