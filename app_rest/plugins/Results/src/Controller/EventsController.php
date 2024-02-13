<?php
declare(strict_types=1);

namespace Results\Controller;

use App\Lib\FullBaseUrl;
use RestApi\Lib\Helpers\PaginationHelper;
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

    public function getList()
    {
        $paginator = new PaginationHelper($this->request);
        $filters = $paginator->processQueryFilters();

        $query = $this->Events->findPaginatedEvents($filters);

        $this->flatResponse = true;
        $this->return = $paginator->getReturnArray($query, FullBaseUrl::host());
    }

    protected function getData($id)
    {
        $this->return = $this->Events->getEventWithRelations($id);
    }
}
