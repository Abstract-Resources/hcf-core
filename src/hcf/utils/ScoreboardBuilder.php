<?php

declare(strict_types=1);

namespace hcf\utils;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\utils\TextFormat;
use function uniqid;

final class ScoreboardBuilder {

    /** @var string */
    public const LIST = 'list';
    public const SIDEBAR = 'sidebar';

    /** @var int */
    public const ASCENDING = 0;
    public const DESCENDING = 1;

    /** @var string */
    private string $objectiveName;

    /**
     * @param string $title
     * @param string $displaySlot
     * @param int    $sortOrder
     */
    public function __construct(
        private string $title,
        private string $displaySlot,
        private int $sortOrder = self::ASCENDING
    ) {
        $this->objectiveName = uniqid('', true);
    }

    public function removePacket(): RemoveObjectivePacket {
        return RemoveObjectivePacket::create($this->objectiveName);
    }

    public function addPacket(): SetDisplayObjectivePacket {
        return SetDisplayObjectivePacket::create(
            $this->displaySlot,
            $this->objectiveName,
            $this->title,
            'dummy',
            $this->sortOrder
        );
    }

    /**
     * @param int    $slot
     * @param string $line
     *
     * @return SetScorePacket[]
     */
    public function fetchLine(int $slot, string $line): array {
        return [
        	$this->getPackets($slot, $line, SetScorePacket::TYPE_REMOVE),
        	$this->getPackets($slot, $line, SetScorePacket::TYPE_CHANGE),
        ];
    }

    /**
     * @param int    $slot
     * @param string $message
     * @param int    $type
     *
     * @return SetScorePacket
     */
    public function getPackets(int $slot, string $message, int $type): SetScorePacket {
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $this->objectiveName;
        $entry->score = $slot;
        $entry->scoreboardId = $slot;

        if ($type === SetScorePacket::TYPE_CHANGE) {
            $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

            $entry->customName = TextFormat::colorize($message) . ' ';
        }

        return SetScorePacket::create($type, [$entry]);
    }
}