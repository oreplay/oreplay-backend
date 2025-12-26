<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use App\Controller\ApiController;
use App\Lib\FullBaseUrl;
use App\Model\Entity\User;

/**
 * @property User[] $users
 * @property mixed $description
 * @property string $federation_id
 * @property Federation $federation
 * @property Stage[] $stages
 * @property bool $is_hidden
 */
class Event extends AppEntity
{
    public const FIRST_EVENT  = '8f3b542c-23b9-4790-a113-b83d476c0ad9';
    public const SECOND_EVENT = 'f8f3ad2c-23b9-4790-a114-b83d47jl0ad9';
    public const THIRD_EVENT  = '8f3b542c-23b9-4790-a115-b83Af4760ad9';
    public const FOURTH_EVENT = '8f3b5adc-23b9-4790-a116-b83Af4760ad9';


    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'is_hidden' => true,
        'description' => true,
        'scope' => true,
        'location' => true,
        'country_code' => true,
        'website' => true,
        'picture' => true,
        'initial_date' => true,
        'final_date' => true,
        'organizer_id' => true,
    ];

    protected array $_virtual = [
        '_links',
    ];

    protected array $_hidden = [
        'deleted',
    ];

    public function _get_links(): array
    {
        return [
            'self' => FullBaseUrl::host() . ApiController::ROUTE_PREFIX . '/events/' . $this->id
        ];
    }

    public function getFirstUser()
    {
        if (!$this->users) {
            return null;
        }
        foreach ($this->users as $usr) {
            return $usr;
        }
        return null;
    }

    public function getVerySimplified(): array
    {
        $array = $this->toArray();
        $stages = [];
        foreach ($array['stages'] as $stage) {
            $stages[] = [
                'id' => $stage['id'],
                'description' => $stage['description'],
            ];
        }
        return [
            'id' => $array['id'],
            'description' => $array['description'],
            'stages' => $stages,
        ];
    }
}
