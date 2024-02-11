<?php
declare(strict_types=1);

namespace Results\Controller;

use Results\Model\Table\EventsTable;

/**
 * @property EventsTable $Events
 */
class EventsController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getMandatoryParams(): array
    {
        return [];
    }

    public function getList()
    {
        $this->return = $this->Events->find()->orderAsc('created')->all();
    }

    protected function getData($id)
    {
        $this->return = $this->Events->getEventWithRelations($id);
    }
}
