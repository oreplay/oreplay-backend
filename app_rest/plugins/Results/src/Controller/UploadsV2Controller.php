<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\Consts\CacheGrp;
use App\Lib\FullBaseUrl;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Exception\ConflictException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\HttpException;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\Import\Iof\IofUploadFactory;
use Results\Lib\Import\Iof\IofUploadOptions;
use Results\Lib\Import\StageUploadLock;
use Results\Lib\Import\UploadProcessor;
use Results\Lib\Publish\ClassResultsPublisher;
use Results\Lib\Publish\NchanChannel;
use Results\Lib\Publish\UploadProgressPublisher;
use Results\Lib\Publish\UploadPublisher;
use Results\Lib\Publish\UploadPublishers;
use Results\Lib\UploadHelper;
use Results\Lib\UploadMessage;
use Results\Lib\UploadMetrics;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RawUploadsTable;
use Results\Model\Table\TokensTable;
use Results\Model\Entity\UploadLog;
use Results\Model\Table\UploadLogsTable;

class UploadsV2Controller extends ApiController
{
    private UploadMetrics $_metrics;
    private ClassesTable $Classes;
    // held for the lifetime of the request: the classes stream lazily from the buffered document
    private ?IofUploadFactory $_iofUploads = null;

    public function isPublicController(): bool
    {
        return true;
    }

    private function _clearUploadCache()
    {
        Cache::clearGroup(CacheGrp::UPLOAD_ENTITIES_GROUP, CacheGrp::UPLOAD);
    }

    private function _getHost()
    {
        $host = FullBaseUrl::host();
        if (str_contains($host, 'http://')) {
            return 'http://www.example.com';
        }
        if (str_contains($host, '127.0.0.1')) {
            return 'http://localhost';
        }
        return $host;
    }

    private function _addNew(UploadHelper $helper): array
    {
        $this->_clearUploadCache();
        if (Configure::read('debug')) {
            $this->_writeLastUploadJson($helper->getData(), TMP . 'lastUpload.json');
        }
        //$this->log('Uploading: ' . " \n\n" . json_encode($helper->getData()), \Psr\Log\LogLevel::DEBUG); // NOSONAR
        $this->_assertDesktopClientAuthenticated($helper);

        //$rawUrl = $this->_getHost() . '/api/v1/events/' . $helper->getEventId() . '/rawUploads';
        //FireAndForget::postJson($rawUrl, $helper->getData(), ['Authorization' => 'Bearer ' . $this->_getBearer()]);

        // the log row describes the upload, so the payload has to be understood before it can be written
        $type = $helper->validateConfigChecker()->preCheckType();
        $lock = new StageUploadLock();
        if (!$lock->acquire($helper->getEventId(), $helper->getStageId())) {
            throw new ConflictException('An upload for this stage is still being processed');
        }
        try {
            $log = UploadLogsTable::load()->saveUploadLog($helper);

            $processor = new UploadProcessor($this->Classes, $this->_publishers($log));
            $processor->process($helper);
            RawUploadsTable::load()->saveFile($log, $helper);
        } finally {
            $lock->release();
        }

        $metrics = $helper->getMetrics();
        $metrics->endTotalTimer();
        return $metrics->toRestArray($type, $log->id);
    }

    private function _publishers(UploadLog $log): UploadPublisher
    {
        $publishers = [new UploadProgressPublisher($log)];
        if (NchanChannel::isConfigured()) {
            $publishers[] = new ClassResultsPublisher(new NchanChannel(), $log->id);
        }
        return new UploadPublishers($publishers);
    }

    private function _assertDesktopClientAuthenticated(UploadHelper $helper): void
    {
        $token = $this->_getBearer();
        if (!TokensTable::load()->isValidEventToken($helper->getEventId(), $token)) {
            throw new ForbiddenException('Invalid Bearer token');
        }
    }

    /**
     * IOF XML reaches us as a raw string: no body parser is registered for it, which is the branch that
     * was always missing rather than plumbing. See docs/upload-xml-input.md 1.1.
     *
     * v2 only. v1 keeps its contract, see docs/uploads-v1-vs-v2.md.
     */
    private function _iofXmlHelper(string $body): UploadHelper
    {
        $this->_iofUploads = new IofUploadFactory($this->_metrics);
        return $this->_iofUploads->helperFor($body, $this->_iofUploadOptions());
    }

    private function _iofUploadOptions(): IofUploadOptions
    {
        return new IofUploadOptions(
            $this->request->getParam('eventID'),
            (string)$this->request->getQuery('stage_id'),
            (string)$this->request->getQuery('tz') ?: null,
            (string)$this->request->getQuery('upload_type') ?: null,
            $this->_isSchemaValidationRequested()
        );
    }

    private function _isSchemaValidationRequested(): bool
    {
        $requested = $this->request->getQuery('validate');
        if ($requested === null || $requested === '') {
            return true;
        }
        return filter_var($requested, FILTER_VALIDATE_BOOLEAN);
    }

    protected function addNew($data)
    {
        $this->Classes = ClassesTable::load();
        $this->flatResponse = true;
        $this->_metrics = UploadMetrics::withoutSavedClasses();
        try {
            $eventId = $this->request->getParam('eventID');
            // an XML body arrives as a raw string, so this branch has to come before anything that
            // expects an array: getReUploadedData() is typed array and would raise a TypeError
            if (is_string($data)) {
                $helper = $this->_iofXmlHelper($data);
            } else {
                $reUploadedData = RawUploadsTable::load()->getReUploadedData($data, $eventId);
                if ($reUploadedData) {
                    $data = $reUploadedData;
                }
                $helper = new UploadHelper($data, $eventId, $this->_metrics);
            }
            if ($this->_isReprocessAllRequested()) {
                $helper->reprocessAll();
            }
            $this->return = $this->_addNew($helper);
        } catch (\PDOException $e) {
            $this->log('Uploads PDOException: ' . $e->getMessage()
                . " \n\n" . json_encode($data)
                . " \n\n" . json_encode($this->return)
            );
            $this->return = $this->respondError($e->getMessage(), $e);
        } catch (DetailedException $e) {
            $this->log('Uploads DetailedException: ' . $e->getMessage() . " \n" . json_encode($data)
                . " \n" . $e->getTraceAsString());
            $this->return = $this->respondError($e->getMessage(), $e);
        } catch (\Throwable $e) {
            $this->log('Uploads GeneralException: ' . $e->getMessage() . " \n" . json_encode($data)
                . " \n" . $e->getTraceAsString());
            // an HttpException carries a message meant for the client, such as the reason a token was
            // refused; anything else is unexpected, and only its type is safe to report
            $exploded = explode('\\', get_class($e));
            $exceptionName = array_pop($exploded);
            if (!$exceptionName) {
                $exceptionName = array_pop($exploded);
            }
            $clientSafe = $e instanceof HttpException ? $e->getMessage() : '';
            $this->return = $this->respondError($clientSafe ?: $exceptionName, $e);
        } finally {
            $this->_clearUploadCache();
        }
    }

    // v1 answers 202 for every failure because its contract with the desktop client says so. v2 is
    // free of that promise and answers a real status, so a client can tell a rejected upload from an
    // accepted one without parsing meta.human. See docs/uploads-v1-vs-v2.md
    private function respondError(string $message, \Throwable $e): array
    {
        $this->response = $this->response->withStatus($this->_errorStatus($e));
        return $this->_metrics->toRestArrayError(UploadMessage::error($this->_errorCode($e), $message));
    }

    private function _errorCode(\Throwable $e): string
    {
        $exploded = explode('\\', get_class($e));
        $name = (string)array_pop($exploded);
        $name = (string)preg_replace('/Exception$/', '', $name) ?: $name;
        if (strtoupper($name) === $name) {
            return strtolower($name);
        }
        return strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    // the range is the filter, not the class: RecordNotFoundException is not an HttpException but
    // carries a real 404, while a PDOException carries a SQLSTATE such as 23000 that must not escape
    private function _errorStatus(\Throwable $e): int
    {
        $code = $e->getCode();
        if (is_int($code) && $code >= 400 && $code <= 599) {
            return $code;
        }
        return 500;
    }

    private function _getBearer(): ?string
    {
        $auth = $this->getRequest()->getHeader('Authorization')[0] ?? null;
        if (!$auth) {
            return null;
        }
        return substr($auth, strlen('Bearer '));
    }

    private function _isReprocessAllRequested(): bool
    {
        return filter_var($this->getRequest()->getQuery('reprocess_all'), FILTER_VALIDATE_BOOLEAN);
    }

    private function _writeLastUploadJson(array $content, string $path)
    {
        $file = new \SplFileObject($path, 'w+');
        $file->fwrite(json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
