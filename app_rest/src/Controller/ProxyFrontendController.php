<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Lib\ProxyFront\FrontUtil;
use Cake\Http\Exception\NotFoundException;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\EventsTable;

class ProxyFrontendController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getList()
    {
        // the frontend CI calls this after a release; kept so the call goes on working
        $this->_renderIndex('/', (bool)$this->getRequest()->getQuery('updateFront'));
    }

    protected function getData($id)
    {
        $path = '/' . $id;
        // the copy the service worker keeps until the next release, see FrontUtil::getIndexHtml()
        $this->_renderIndex($path, $path === '/index.html');
    }

    private function _renderIndex(string $path, bool $revalidate)
    {
        $lang = $this->_getSimpleLang();
        $description = $this->_getEventDateAndTitle($path) ?? $this->_getDescription($lang);
        $html = FrontUtil::getIndexHtml($this->_getFrontDomain(), $revalidate);
        $stringBody = FrontUtil::buildHtml($html, $lang, $description, SwaggerJsonController::version());

        $this->autoRender = false;
        $this->response = $this->response->withStringBody($stringBody)
            ->withHeader('Cache-Control', 'no-cache');
        return $this->response;
    }

    private function _getEventDateAndTitle(string $path): ?string
    {
        if (!preg_match('#^/competitions/([0-9a-f-]{36})#', $path, $matches)) {
            return null;
        }
        $eventId = $matches[1];
        $event = EventsTable::load()->getRecentEvents()[$eventId] ?? null;
        if (!$event) {
            return null;
        }
        $dateAndTitle = $event['initial_date'] . ' ' . $event['description'];
        $club = $this->getRequest()->getQuery('club');
        if (is_string($club) && ClubsTable::load()->existsInEvent($eventId, $club)) {
            return $dateAndTitle . ' - ' . $club;
        }
        return $dateAndTitle;
    }

    private function _getFrontDomain()
    {
        $domain = $_SERVER['FRONT_DOMAIN'] ?? '';
        if (!$domain) {
            throw new NotFoundException('Front domain not defined');
        }
        return $domain;
    }

    private function _getDescription(string $lang)
    {
        return match ($lang) {
            'es' => 'O-Replay sigue eventos de orientación en directo',
            default => 'O-Replay is the home to orienteering live results',
        };
    }

    private function _getSimpleLang(): string
    {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        if (!$lang) {
            return 'en';
        }
        $exploded = explode(',', $lang)[0];
        return explode('-', $exploded)[0];
    }
}

