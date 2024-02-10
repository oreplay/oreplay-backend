<?php

namespace Results\Model\Entity;

use App\Controller\ApiController;
use App\Lib\FullBaseUrl;
use Cake\ORM\Entity;

class Event extends Entity
{
    public const FIRST_EVENT = '8f3b542c-23b9-4790-a113-b83d476c0ad9';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
        'initial_date' => true,
        'final_date' => true,
    ];

    protected $_virtual = [
        '_links',
    ];

    protected $_hidden = [
        'deleted',
    ];

    public function _get_links(): array
    {
        return [
            'self' => FullBaseUrl::host() . ApiController::ROUTE_PREFIX . '/events/' . $this->id
        ];
    }
}
