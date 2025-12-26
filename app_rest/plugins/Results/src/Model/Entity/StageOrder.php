<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property int $stage_order
 * @property string $description
 * @property string $event_id
 * @property string $stage_id
 * @property string $original_stage_id
 */
class StageOrder extends AppEntity
{
    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
    ];

    protected array $_hidden = [
        'event_id',
        'stage_id',
        'original_stage_id',
        'stage_order',
        'created',
        'modified',
        'deleted',
    ];

    private string $extraNote = '';

    public function setExtraNote(string $note): static
    {
        $this->extraNote = $note;
        return $this;
    }

    public function _getDescription(): string
    {
        $description = $this->_fields['description'];
        if ($this->extraNote) {
            $description .= ' [' . $this->extraNote . ']';
        }
        return $description;
    }
}
