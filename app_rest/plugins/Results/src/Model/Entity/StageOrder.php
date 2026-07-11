<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property int $stage_order
 * @property string $description
 * @property string $event_id
 * @property string $stage_id
 * @property string $original_stage_id
 * @property string $original_event_id
 * @property \Cake\I18n\FrozenTime $computed
 * @property \Cake\I18n\FrozenTime $start
 * @property bool $is_official
 */
class StageOrder extends AppEntity
{
    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
        'start' => true,
        'is_official' => true,
    ];

    protected array $_hidden = [
        'event_id',
        'stage_id',
        'original_stage_id',
        'original_event_id',
        'stage_order',
        'is_official',
        'computed',
        'start',
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

    public function toArrayManagement(): array
    {
        return $this->toChild('StageOrderManagement', [
            'id' => $this->id,
            'stage_order' => $this->stage_order,
            'description' => $this->description,
            'original_event_id' => $this->original_event_id,
            'original_stage_id' => $this->original_stage_id,
            'is_official' => $this->is_official,
            'start' => $this->start,
            'created' => $this->created,
        ]);
    }
}
