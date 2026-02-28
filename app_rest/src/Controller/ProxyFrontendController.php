<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Lib\ProxyFront\FrontUtil;
use Cake\Http\Exception\NotFoundException;

class ProxyFrontendController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getList()
    {
        $this->getData('');
    }

    protected function getData($id)
    {
        $path = '/' . $id;
        if (str_starts_with($path, '/locales/') || str_starts_with($path, '/assets/')) {
            $this->redirect($this->_getFrontDomain() . $path);
        }
        $lang = $this->_getSimpleLang();

        $html = '';
        $description = $this->_getDescription($lang);
        $stringBody = $this->_getFallbackHtml($description, $html);

        $this->autoRender = false;
        $this->response = $this->response->withStringBody($stringBody);
        return $this->response;
    }

    private function _getFrontDomain()
    {
        $domain = $_SERVER['FRONT_DOMAIN'] ?? '';
        if (!$domain) {
            throw new NotFoundException('Front domain not defined');
        }
        return $domain;
    }

    private function _getFallbackHtml(string $description, string $html = ''): string
    {
        $version = SwaggerJsonController::version();
        $url = $this->_getFrontDomain();
        $index = FrontUtil::getIndexJson($url);
        $og = FrontUtil::getOgImage("Home for orienteering\nevents");
        return '<!doctype html>
            <html lang="en" translate="no">
              <head>
                <meta charset="UTF-8" />
                <meta name="google" content="notranslate" />
                <link rel="icon" type="image/jpg" href="' . $url . '/logo.svg" />
                <link rel="icon" type="image/x-icon" href="' . $url . '/logo.png" />
                <meta data-hid="image" itemprop="image" content="' . $url . '/logo.png" />
                <meta data-hid="og:image" property="og:image" content="' . $og . '" />
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <meta data-hid="title" itemprop="title" content="O-Replay" />
                <meta data-hid="og:title" property="og:title" content="O-Replay" />
                <meta data-hid="og:site_name" property="og:site_name" content="O-Replay" />
                <meta
                  data-hid="apple-mobile-web-app-title"
                  name="apple-mobile-web-app-title"
                  content="O-Replay"
                />
                <meta data-hid="language" name="language" content="en" />
                <meta
                  data-hid="description"
                  itemprop="description"
                  content="' . $description . '"
                />
                <meta
                  data-hid="og:image:alt"
                  property="og:image:alt"
                  content="' . $description . '"
                />
                <meta
                  data-hid="og:description"
                  property="og:description"
                  content="' . $description . '"
                />
                <meta data-hid="og:type" property="og:type" content="website" />
                <title>O-Replay</title>
                <script>console.log("SSR v' . $version . '")</script>
                <script>window._ssr="' . $version . '"</script>
                <script type="module" crossorigin src="' . $url . '/assets/' . $index . '"></script>
              </head>
              <body style="margin: 0; height: 100vh">
                <div id="root">' . $html . '</div>
                <noscript>' . $description . '</noscript>
              </body>
            </html>';
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
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $exploded = explode(',', $lang)[0];
        return explode('-', $exploded)[0];
    }
}

