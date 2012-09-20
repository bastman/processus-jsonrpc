<?php

namespace Processus\Lib\JsonRpc2;

    class Gateway implements GatewayInterface
    {



        protected $_isDebugEnabled = false;

        /**
         * @var $_server Server
         */
        protected $_server;

        /**
         * @var \Processus\Interfaces\InterfaceAuthModule
         */
        protected $_authModule;


        /**
         * @var array
         */
        protected $_rpcQueue = array();
        /**
         * @var bool
         */
        protected $_isRequestBatched = false;
        /**
         * @var string
         */
        protected $_requestText = '';
        /**
         * @var array
         */
        protected $_requestData = array();

        /**
         * @var array
         */
        protected $_responseHeaders = array(
            "Content-Type: application/json; charset=utf-8",
        );

        /**
         * @var array
         */
        protected $_config = array(
            'enabled' => true,
            'requestBatchMaxItems' => 100,
            'serverClassName' => '{{NAMESPACE}}\\Server',
            'authClassName' => '{{NAMESPACE}}\\Auth',
        );


        /**
         * @param $key string
         * @return mixed|null
         */
        public function getConfigValue($key)
        {
            $result = null;

            $config = $this->_config;

            if (!array_key_exists($key, $config)) {

                return $result;
            }

            return $config[$key];
        }

        /**
         * @param $value bool
         * @return Gateway
         */
        public function setIsDebugEnabled($value)
        {
            $this->_isDebugEnabled = ($value === true);

            return $this;
        }

        /**
         * @return bool
         */
        public function getIsDebugEnabled()
        {
            return ($this->_isDebugEnabled === true);
        }

        /**
         * @return bool
         */
        public function getIsRequestBatched()
        {
            return ($this->_isRequestBatched === true);
        }



        // =========== SERVER ======================

        /**
         * @return Server
         */
        public function newServer()
        {
            $serverClassName = '' . $this->getConfigValue('serverClassName');

            $reflectionClass = new \ReflectionClass($this);
            $namespaceName = $reflectionClass->getNamespaceName();

            $serverClassName = str_replace(
                array(
                    '{{NAMESPACE}}',
                ),
                array(
                    $namespaceName,

                ),
                $serverClassName
            );


            try {

                /**
                 * @var $server Server
                 */
                $server = new $serverClassName();

            } catch(\Exception $e) {
                // NOP
            }

            if(!
            ($server instanceof
                Server)
            ) {
                throw new \Exception(
                    GatewayErrorType::ERROR_GATEWAYCONFIG_INVALID_SERVER
                );
            }

            $server->setGateway($this);

            return $server;

        }

        /**
         * @return Server
         */
        public function getServer()
        {
            if (!($this->_server instanceof Server)) {
                $this->_server = $this->newServer();
            }

            return $this->_server;
        }

        /**
         * @param Server $server
         * @return Gateway
         */
        public function setServer(Server $server)
        {
            $server->setGateway($this);
            $this->_server = $server;

            return $this;
        }

        /**
         * @return Gateway
         */
        public function unsetServer()
        {
            $this->_server = null;

            return $this;
        }

        // =========== RPC ======================


        /**
         * @return Rpc
         */
        public function newRpc()
        {
            return $this->getServer()->newRpc();
        }


        // =========== REQUEST: TEXT ======================

        /**
         * @param $requestText string
         * @return Gateway
         * @throws \Exception
         */
        public function setRequestText($requestText)
        {
            if (!is_string($requestText)) {

                throw new \Exception(
                    'Invalid parameter requestText. '
                        . __METHOD__
                        . get_class($this)
                );
            }

            $this->_requestText = $requestText;

            return $this;
        }

        /**
         * @return string
         */
        public function getRequestText()
        {
            return '' . $this->_requestText;
        }

        /**
         * @return string
         */
        protected function _fetchRequestText()
        {
            $requestText = '' . file_get_contents('php://input');

            return $requestText;
        }


        // =========== REQUEST: DATA ======================


        /**
         * @param array $data
         * @return Gateway
         */
        public function setRequestData(array $data = array())
        {
            $this->_requestData = $data;

            return $this;
        }

        /**
         * @return array
         */
        public function getRequestData()
        {
            return $this->_requestData;
        }

        /**
         * @return array
         */
        protected function _fetchRequestData()
        {
            $result = array();

            $requestText = '' . $this->getRequestText();
            if ($requestText === '') {
                $requestText = '' . $this->_fetchRequestText();
                $this->_requestText = $requestText;
            }

            $requestData = null;
            try {
                $requestData = json_decode($requestText, true);
            } catch (\Exception $e) {
                // NOP
            }

            if (!is_array($requestData)) {

                return $result;
            }

            return $requestData;
        }


        // =========== RPC: QUEUE ======================

        /**
         * @return array
         */
        public function getRpcQueue()
        {
            if (!is_array($this->_rpcQueue)) {
                $this->_rpcQueue = array();
            }

            return $this->_rpcQueue;
        }


        /**
         * @param Rpc $rpc
         * @return Gateway
         */
        public function rpcQueueAddItem(Rpc $rpc)
        {
            $this->_rpcQueue[] = $rpc;

            return $this;
        }

        /**
         *
         */
        protected function _processRpcQueue()
        {
            $rpcQueue = $this->getRpcQueue();
            foreach ($rpcQueue as $rpc) {
                $this->_processRpcQueueItem($rpc);
            }
        }


        // =========== RPC: QUEUE-ITEM ======================

        /**
         * @param Rpc $rpc
         */
        protected function _processRpcQueueItem(Rpc $rpc)
        {
            $server = $this->getServer();

            $server->processRpc($rpc);

        }


        // =========== RESPONSE: HEADERS ======================


        /**
         * @return array
         */
        public function getResponseHeaders()
        {
            if (!is_array($this->_responseHeaders)) {
                $this->_responseHeaders = array();
            }

            return $this->_responseHeaders;
        }

        /**
         * @param $header string
         * @return Gateway
         */
        public function responseHeadersAddItem($header)
        {
            $headersList = $this->getResponseHeaders();
            $headersList[] = '' . $header;

            $this->_responseHeaders[] = $header;

            return $this;
        }


        // =========== RESPONSE: DATA ======================


        /**
         * @param array $responseData
         * @return string
         */
        protected function _encodeResponseData(
            array $responseData = array()
        ) {
            $result = '';
            try {
                $responseText = '' . json_encode($responseData);

                return $responseText;

            } catch (\Exception $e) {
                // NOP
            }

            return $result;
        }

        /**
         * @param array $responseHeadersList
         * @return Gateway
         */
        protected function _sendResponseHeaders(
            array $responseHeadersList = array()
        ) {
            if (count($responseHeadersList) < 1) {
                $responseHeadersList[] =
                    "Content-Type: application/json; charset=utf-8";
            }

            $dict = array();

            foreach ($responseHeadersList as $responseHeader) {
                if (!is_string($responseHeader)) {

                    continue;
                }

                $dictKey = '' . strtolower($responseHeader);
                if (!array_key_exists($dictKey, $dict)) {

                    header($responseHeader);
                    $dict[$dictKey] = $responseHeader;
                }

            }

            return $this;
        }

        /**
         * @param $responseText string
         */
        protected function _sendResponseText($responseText)
        {
            $responseText = '' . $responseText;

            echo $responseText;
        }


        // =========== GATEWAY: RUN ======================

        /**
         * @return Gateway
         * @throws \Exception
         */
        public function run()
        {

            try {

                $this->_requireIsEnabled();
                $this->_requireIsAuthorized();

                // parse request: is request valid json ?
                $requestData = $this->getRequestData();
                if (count($requestData) < 1) {
                    $requestData = $this->_fetchRequestData();
                }
                if (count(array_keys($requestData)) < 1) {

                    throw new \Exception(
                        GatewayErrorType::ERROR_GATEWAY_INVALID_REQUEST
                    );
                }
                $this->_requestData = $requestData;

                // parse request: is request batched ?
                $batchItemCount = (int)count($requestData);
                $isAssocArray = (array_keys($requestData)
                    !== range(0, $batchItemCount - 1)
                );
                $isBatched = (!$isAssocArray);
                $this->_isRequestBatched = $isBatched;

                $batch = $requestData;
                if (!$isBatched) {
                    $batch = array(
                        $requestData
                    );
                    $batchItemCount = 1;
                }

                // parse request: check min/maxBatchCount
                $requestBatchMaxItems = (int)$this->getConfigValue(
                    'requestBatchMaxItems'
                );

                if ($batchItemCount < 1) {

                    throw new \Exception(
                        GatewayErrorType::ERROR_GATEWAY_REQUEST_BATCH_IS_EMPTY
                    );

                }
                if ($batchItemCount > $requestBatchMaxItems) {

                    throw new \Exception(
                        GatewayErrorType::ERROR_GATEWAY_REQUEST_BATCH_TOO_LARGE
                    );
                }

                // rpc queue: append items
                foreach ($batch as $rpcDataItem) {

                    if (!is_array($rpcDataItem)) {
                        $rpcDataItem = array();
                    }

                    $rpc = $this->newRpc();
                    $rpc->getRequest()->setData($rpcDataItem);
                    $rpc->getResponse()->setId(
                        $rpc->getRequest()->getId()
                    );
                    $rpc->getResponse()->setVersion(
                        $rpc->getRequest()->getVersion()
                    );

                    $this->rpcQueueAddItem($rpc);
                }

                // rpc queue: process items
                $rpcQueue = $this->getRpcQueue();
                foreach ($rpcQueue as $rpc) {
                    $this->_processRpcQueueItem($rpc);
                }

                // response:
                $rpcQueue = $this->getRpcQueue();

                $batchResponse = array();

                foreach ($rpcQueue as $rpc) {
                    /**
                     * @var $rpc Rpc
                     */

                    $batchResponseItemData = $this->_getRpcResponseData(
                        $rpc
                    );

                    $batchResponse[] = $batchResponseItemData;
                }

                $responseHeaders = $this->getResponseHeaders();

                $this->_sendResponseHeaders($responseHeaders);
                $responseText = '';
                if ($isBatched) {
                    $responseText = $this->_encodeResponseData(
                        $batchResponse
                    );
                } else {
                    $responseText = $this->_encodeResponseData(
                        $batchResponse[0]
                    );
                }

                if ($responseText === '') {
                    throw new \Exception(
                        GatewayErrorType::ERROR_GATEWAY_INVALID_RESPONSE
                    );
                }

                $this->_sendResponseText($responseText);

            } catch (\Exception $e) {

                $this->_onGatewayError($e);
            }

            return $this;
        }

        /**
         *
         */
        public function init()
        {

            // do fancy stuff here
            // ... set is debugMode?

        }

        /**
         * @throws \Exception
         */
        protected function _requireIsEnabled()
        {
            $isEnabled = ($this->getConfigValue('enabled') === true);
            if (!$isEnabled) {

                throw new \Exception(
                    GatewayErrorType::ERROR_GATEWAY_NOT_ENABLED
                );
            }
        }

        /**
         * @param \Exception $error
         * @return Gateway
         */
        protected function _onGatewayError(\Exception $error)
        {
            $result = $this;


            $isDebugEnabled = $this->getIsDebugEnabled();

            $classNameNice = str_replace(
                array('_', '\\'),
                '.',
                get_class($this)
            );

            $errorMessage = '';

            switch ($error->getMessage()) {

                case GatewayErrorType::ERROR_GATEWAY_NOT_ENABLED:

                    header('HTTP/1.0 403 Forbidden');

                    $errorMessage .= 'GATEWAY NOT ENABLED';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);
                    echo $errorMessage;

                    break;

                case GatewayErrorType::ERROR_GATEWAY_INVALID_REQUEST:

                    header('HTTP/1.0 400 Bad Request');

                    $errorMessage .= 'INVALID GATEWAY REQUEST';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);

                    break;


                case GatewayErrorType::ERROR_GATEWAY_REQUEST_BATCH_IS_EMPTY:

                    header('HTTP/1.0 400 Bad Request');

                    $errorMessage .= 'INVALID GATEWAY REQUEST BATCH IS EMPTY';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);

                    break;

                case GatewayErrorType::ERROR_GATEWAY_REQUEST_BATCH_TOO_LARGE:

                    header('HTTP/1.0 400 Bad Request');

                    $errorMessage .= 'INVALID GATEWAY REQUEST BATCH TOO LARGE';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);

                    break;

                case GatewayErrorType::ERROR_GATEWAY_INVALID_RESPONSE:

                    header('HTTP/1.0 500 Internal Server Error');

                    $errorMessage .= 'INVALID GATEWAY RESPONSE';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);

                    break;

                case GatewayErrorType::ERROR_GATEWAYCONFIG_INVALID_SERVER:

                    header('HTTP/1.0 500 Internal Server Error');

                    $errorMessage .= 'INVALID GATEWAY CONFIG';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                        $errorMessage .= ' : Gateway.config.server invalid';
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);

                    break;

                case GatewayErrorType::ERROR_GATEWAYCONFIG_INVALID_AUTHMODULE:

                    header('HTTP/1.0 500 Internal Server Error');

                    $errorMessage .= 'INVALID GATEWAY CONFIG';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                        $errorMessage .= ' : Gateway.config.authModule invalid';
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);

                    break;

                case GatewayErrorType::ERROR_GATEWAY_AUTH_REQUIRED:

                    header('HTTP/1.0 403 Forbidden');

                    $errorMessage .= 'AUTHORISATION REQUIRED';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                        $errorMessage .= ' : gateway.authModule access denied';
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);

                    break;

                default:

                    header('HTTP/1.0 500 Internal Server Error');

                    $errorMessage .= 'UNKNOWN GATEWAY ERROR';
                    if ($isDebugEnabled) {
                        $errorMessage .= ' : ' . $classNameNice;
                    }

                    header('x-processus-gateway-error: ' . $errorMessage);

                    break;
            }


            return $result;
        }


        /**
         * @param RPC $rpc
         * @return array
         */
        protected function _getRpcResponseData(RPC $rpc)
        {
            $isDebugEnabled = $this->getIsDebugEnabled();

            $rpcResponse = $rpc->getResponse();

            $responseData = array(
                'id' => $rpcResponse->getId(),
                'version' => $rpcResponse->getVersion(),
                'jsonrpc' => $rpcResponse->getJsonrpc(),
                'result' => $rpcResponse->getResult(),
                'error' => null,
            );

            if ($isDebugEnabled) {
                $responseData['debug'] = $this->_getResponseDataDebugInfo();
            }


            if (
                ($responseData['version'] === '')
                || ($responseData['version'] === null)
            ) {
                unset($responseData['version']);
            }
            if (
                ($responseData['jsonrpc'] === '')
                || ($responseData['jsonrpc'] === null)
            ) {
                unset($responseData['jsonrpc']);
            }

            if (!$rpcResponse->hasException()) {

                return $responseData;
            }


            $rpcException = $rpcResponse->getException();

            $error = array(
                'message' => $rpcException->getMessage(),
                'class' => str_replace(
                    array('_', '\\'),
                    '.',
                    get_class($rpcException)
                ),
                'gateway' => str_replace(
                    array('_', '\\'),
                    '.',
                    get_class($this)
                ),
                'server' => str_replace(
                    array('_', '\\'),
                    '.',
                    get_class(
                        $this->getServer()
                    )
                ),
                'code' => $rpcException->getCode(),
                'file' => $rpcException->getFile(),
                'line' => $rpcException->getLine(),
                'stackTrace' => $rpcException->getTraceAsString(),
            );

            if (!$isDebugEnabled) {
                $unsetKeys = array(
                    'code',
                    'file',
                    'line',
                    'stackTrace',
                    'class',
                    'gateway',
                    'server',
                );
                foreach ($unsetKeys as $key) {
                    unset($error[$key]);
                }

                $error['message'] = 'AN ERROR OCCURRED';
            }

            $responseData['result'] = null;
            $responseData['error'] = $error;


            return $responseData;


        }


        // =========== PROCESSUS: AUTH ======================

        /**
         * @return \Processus\Interfaces\InterfaceAuthModule $authModule
         */
        public function newAuthModule()
        {
            $authClassName = '' . $this->getConfigValue('authClassName');

            $reflectionClass = new \ReflectionClass($this);
            $namespaceName = $reflectionClass->getNamespaceName();

            $authClassName = str_replace(
                array(
                    '{{NAMESPACE}}',
                ),
                array(
                    $namespaceName,

                ),
                $authClassName
            );

            $authModule = null;
            try {
                /**
                 * @var $authModule Auth
                 */
                $authModule = new $authClassName();

            } catch(\Exception $e) {

            }

            if(!
                ($authModule instanceof
                    \Processus\Interfaces\InterfaceAuthModule)
            ) {
                throw new \Exception(
                    GatewayErrorType::ERROR_GATEWAYCONFIG_INVALID_AUTHMODULE
                );
            }

            return $authModule;

        }


        /**
         * @param \Processus\Interfaces\InterfaceAuthModule $authModule
         * @return Gateway
         */
        public function setAuthModule(
                \Processus\Interfaces\InterfaceAuthModule $authModule
            )
        {
            $this->_authModule = $authModule;

            return $this;
        }

        /**
         * @return Gateway
         *
         */
        public function unsetAuthModule()
        {
            $this->_authModule = null;

            return $this;
        }


        /**
         * @return null | \Processus\Interfaces\InterfaceAuthModule
         */
        public function getAuthModule()
        {
            if (!
                ($this->_authModule
                    instanceof \Processus\Interfaces\InterfaceAuthModule)
            ) {
                $authModule = $this->newAuthModule();
                $this->_authModule = $authModule;
            }

            return $this->_authModule;

        }

        /**
         * @throws \Exception
         */
        protected function _requireIsAuthorized()
        {
            $authModule = $this->getAuthModule();

            $isAuthorized = ($authModule->isAuthorized()===true);

            if(!$isAuthorized) {
                throw new \Exception(
                    GatewayErrorType::ERROR_GATEWAY_AUTH_REQUIRED
                );
            }

        }
        // =========== PROCESSUS: MISC ======================

        /**
         * @return \Processus\Lib\Profiler\ProcessusProfiler
         */
        public function getProcessusProfiler()
        {
            return \Processus\Lib\Profiler\ProcessusProfiler::getInstance();
        }


        /**
         * @return \Processus\Lib\System\System
         */
        public function getProcessusSystem()
        {
            return \Processus\Lib\System\System::getInstance();
        }


        /**
         * @return \Processus\Lib\Server\ServerInfo
         */
        public function getProcessusServerParams()
        {
            return \Processus\Lib\Server\ServerInfo::getInstance();
        }

        /**
         * @return \Processus\ProcessusBootstrap
         */
        public function getProcessusBootstrap()
        {
            return \Processus\ProcessusContext::getInstance()->getBootstrap();
        }


        /**
         * @return array
         */
        protected function _getResponseDataDebugInfo()
        {

            $profiler = $this->getProcessusProfiler();
            $system = $this->getProcessusSystem();
            $serverParams = $this->getProcessusServerParams();
            $bootstrap = $this->getProcessusBootstrap();

            $memory = array(
                'usage' => $system->getMemoryUsage(),
                'usage_peak' => $system->getMemoryPeakUsage()
            );

            $app = array(
                'start' => $profiler->applicationProfilerStart(),
                'end' => $profiler->applicationProfilerEnd(),
                'duration' => $profiler->applicationDuration()
            );

            $system = array(
                'request_time' => $serverParams->getRequestTime()
            );

            $requireList = $bootstrap->getFilesRequireList();

            $fileStack = array(
                'list' => $requireList,
                'total' => count($requireList)
            );

            $debugInfo = array(
                'gateway' => get_class($this),
                'server' => get_class($this->getServer()),
                'memory' => $memory,
                'app' => $app,
                'system' => $system,
                'profiling' => $profiler->getProfilerStack(),
                'fileStack' => $fileStack,
            );

            return $debugInfo;

        }


    }


