<?php

namespace App\Lib\Error;

use App\Lib\Exception\DetailedException;
use App\Lib\Validator\ValidationException;
use Cake\Core\Configure;
use Cake\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ExceptionRenderer extends \Cake\Error\ExceptionRenderer
{

    protected function _template(Throwable $exception, string $method, int $code): string
    {
        $isHttpCode = $code >= 400 && $code < 600;
        if ($isHttpCode) {
            return $this->template = $code < 500 ? 'error400' : 'error500';
        }
        return parent::_template($exception, $method, $code);
    }

    public function render(): ResponseInterface
    {
        $exception = $this->error;
        $code = $this->getHttpCode($exception);
        $method = $this->_method($exception);
        $template = $this->_template($exception, $method, $code);

        if (method_exists($this, $method)) {
            return $this->_customMethod($method, $exception);
        }

        $message = $this->_message($exception, $code);
        $response = $this->controller->getResponse();
        $reasonPhrase = '';
        if (method_exists($exception, 'getReasonPhrase')) {
            $reasonPhrase = $exception->getReasonPhrase();
        }
        $response = $response->withStatus($code, $reasonPhrase);

        $viewVars = $this->_getAnwser($response->getReasonPhrase(), $exception, $message);
        $serialize = array_keys($viewVars);

        $this->controller->set($viewVars);
        $this->controller->viewBuilder()->setOption('serialize', $serialize);

        $this->controller->setResponse($response);

        return $this->_outputMessage($template);
    }

    private function _getAnwser(string $errorPhrase, ?Throwable $exception, string $message)
    {
        $isDebugOn = Configure::read('debug');
        $toRet = [
            'error' => $errorPhrase,
        ];
        if ($exception) {
            $code = $this->getHttpCode($exception);
            if (!$code) {
                $code = 500;
            }
            $toRet['code'] = $code;
            if ($exception instanceof DetailedException) {
                $toRet['error'] = namespaceSplit(get_class($exception))[1];
                $toRet['message'] = $exception->getMessage();
            }
            if ($exception instanceof ValidationException) {
                $toRet['error'] = 'Validation error';
                $toRet['error_fields'] = $exception->getValidationErrors();
                Log::write('debug', 'ValidationException: ' . $message);
            }
        }
        if ($isDebugOn) {
            $toRet['message'] = $message;
        }
        if ($exception) {
            if ($isDebugOn) {
                $toRet['exception'] = get_class($exception);

                $toRet['file'] = $exception->getFile();
                $toRet['line'] = $exception->getLine();
                $toRet['details'] = explode("\n", $exception->getTraceAsString());
            }
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $toRet['request'] = $this->_getRequestUrl();
        }
        if (!isset($toRet['error_fields']) && !isset($toRet)) {
            $toRet['details'] = ['Get more details from the logs at '.date('Y-m-d H:i:s').', or ask support'];
        }
        return $toRet;
    }

    private function _isHttps() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']
            || isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
        ) {
            return true;
        }
        return false;
    }

    private function _getRequestUrl()
    {
        $request = $_SERVER['REQUEST_URI'];
        if (isset($_SERVER['HTTP_HOST'])) {
            if ($this->_isHttps()) {
                $scheme = 'https://';
            } else {
                $scheme = 'http://';
            }
            $request = $scheme . $_SERVER['HTTP_HOST'] . $request;
        }
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $request = $_SERVER['REQUEST_METHOD'] . ': ' . $request;
        }
        return $request;
    }
}
