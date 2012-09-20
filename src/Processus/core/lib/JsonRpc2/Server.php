<?php

namespace Processus\Lib\JsonRpc2;

    /**
     * @see: http://www.jsonrpc.org/specification
     */
    class Server
    {

        /**
         * @var array
         */
        protected $_serviceInfoCache = array();

        /**
         * @var \ReflectionClass
         */
        protected $_reflectionClass;

        /**
         * @var array
         */
        protected $_servicesList = array(

            array(
                "serviceName" => "Ping",
                "className" => "{{NAMESPACE}}\WebService\Ping",
                "isValidateMethodParamsEnabled" => true,
                "classMethodFilter" => array(
                    "allow" => array(
                        "*",
                    ),
                    "deny" => array(
                        //'*get*',
                        "*myPrivateMethod"
                    ),
                ),

            ),


        );

        /**
         * @return array
         */
        public function getServicesList()
        {
            return $this->_servicesList;
        }


        /**
         * cache services list as dictionary
         * @return array
         */
        public function getServiceInfoCache()
        {
            if (count($this->_serviceInfoCache) < 1) {

                $dict = array();

                $servicesList = $this->getServicesList();

                $namespaceName = $this->getReflectionClass()
                    ->getNamespaceName();

                foreach ($servicesList as $serviceConfig) {

                    $serviceInfo = new ServiceInfo();
                    $serviceInfo->setData($serviceConfig);

                    $serviceClassName = $serviceInfo->getClassName();
                    $serviceClassName = str_replace(
                        array(
                            '{{NAMESPACE}}',
                        ),
                        array(
                            $namespaceName,
                        ),
                        $serviceClassName
                    );
                    $serviceInfo->setClassName($serviceClassName);

                    $dictKey = $serviceInfo->getServiceUid();
                    $dict[$dictKey] = $serviceInfo;

                }

                $this->_serviceInfoCache = $dict;
            }

            return $this->_serviceInfoCache;
        }


        /**
         * @return \ReflectionClass
         */
        public function getReflectionClass()
        {
            if (!($this->_reflectionClass instanceof \ReflectionClass)) {

                $this->_reflectionClass = new \ReflectionClass($this);
            }

            return $this->_reflectionClass;
        }

        /**
         * @param Gateway $gateway
         * @return Server
         */
        public function setGateway(Gateway $gateway)
        {
            $this->_gateway = $gateway;

            return $this;
        }


        /**
         * @return Rpc
         */
        public function newRpc()
        {
            $rpc = new Rpc();

            return $rpc;
        }

        /**
         * @param Rpc $rpc
         * @return Server
         * @throws \Exception
         */
        public function processRpc(Rpc $rpc)
        {

            try {

                $request = $rpc->getRequest();
                $response = $rpc->getResponse();
                $response->setId($request->getId());
                $response->setVersion($request->getVersion());
                $response->setJsonrpc($request->getJsonrpc());

                $rpcMethod = $request->getMethod();
                $rpcParams = $request->getParams();


                $serviceInfo = $this->_getServiceInfoByRpcMethod($rpcMethod);
                $serviceClassName = $serviceInfo->getClassName();
                $serviceClassInstance = null;
                try {

                    if ((!empty($serviceClassName)) && (class_exists(
                        $serviceClassName
                    ))
                    ) {
                        $serviceClassInstance = new $serviceClassName();
                    }

                } catch (\Exception $e) {
                    // NOP
                }
                if (!($serviceClassInstance instanceof Service)) {
                    throw new \Exception(
                        'INVALID RPC.METHOD: SERVICE NOT FOUND'
                    );
                }

                $serviceMethodName = $this->_getServiceMethodNameByRpcMethod(
                    $rpcMethod
                );


                $reflectionClass = new \ReflectionClass($serviceClassInstance);
                if (!$reflectionClass->hasMethod($serviceMethodName)) {

                    throw new \Exception(
                        'INVALID RPC.METHOD: SERVICE-METHOD NOT FOUND'
                    );
                }

                $reflectionMethod = $reflectionClass->getMethod(
                    $serviceMethodName
                );

                $this->_validateServiceMethod(
                    $reflectionMethod,
                    $serviceInfo
                );

                $reflectionMethodArgs = $reflectionMethod->getParameters();
                $this->_validateServiceMethodArgs(
                    $reflectionMethod,
                    $serviceInfo,
                    $rpcParams
                );
                $serviceMethodArgs = $rpcParams;
                // named  or positional parameters?
                $isNamedRpcParams = (array_keys($rpcParams)
                    !== range(0, count($rpcParams) - 1)
                );
                if ($isNamedRpcParams) {
                    $serviceMethodArgs = array();
                    foreach ($reflectionMethodArgs as $reflectionParameter) {
                        $key = $reflectionParameter->getName();
                        $value = null;
                        if (array_key_exists($key, $rpcParams)) {
                            $value = $rpcParams[$key];
                        } else {

                            if (
                                ($reflectionParameter->isOptional())
                                && (
                                $reflectionParameter->isDefaultValueAvailable()
                                )
                            ) {
                                $value =
                                    $reflectionParameter->getDefaultValue();
                            }

                        }
                        $serviceMethodArgs[] = $value;
                    }
                }


                $this->_invokeServiceMethod(
                    $rpc,
                    $serviceClassInstance,
                    $reflectionMethod,
                    $serviceMethodArgs
                );

                $this->_onRpcResult($rpc);

            } catch (\Exception $e) {

                $rpc->getResponse()->setException($e);

                $this->_onRpcException($rpc);

            }

            return $this;
        }


        /**
         * @param \ReflectionMethod $reflectionMethod
         * @param ServiceInfo $serviceInfo
         * @param array $methodArgs
         * @throws \Exception
         */
        protected function _validateServiceMethodArgs(
            \ReflectionMethod $reflectionMethod,
            ServiceInfo $serviceInfo,
            array $methodArgs = array()
        ) {

            if (!$serviceInfo->getIsValidateMethodParamsEnabled()) {

                return;
            }

            $reflectionParameters = $reflectionMethod->getParameters();

            $methodArgs = (array)$methodArgs;
            $numParamsExpected = $reflectionMethod->getNumberOfParameters();
            $numParamsRequired =
                $reflectionMethod->getNumberOfRequiredParameters();
            $numParamsOptional = $numParamsExpected - $numParamsRequired;
            $numParamsGiven = count($methodArgs);
            $numParamsMissing =
                $numParamsExpected - $numParamsGiven - $numParamsOptional;

            if ($numParamsMissing > 0) {

                $paramNames = array();
                $paramNamesGiven = array_keys($methodArgs);
                $paramNamesOptional = array();
                $paramNamesRequired = array();
                $paramNamesMissing = array();
                foreach ($reflectionParameters as $reflectionParameter) {
                    $paramName = $reflectionParameter->getName();
                    $paramNames[] = $paramName;
                    if ($reflectionParameter->isOptional()) {
                        $paramNamesOptional[] = $paramName;
                    } else {
                        $paramNamesRequired[] = $paramName;

                        if (!in_array($paramName, $paramNamesGiven, true)) {
                            $paramNamesMissing[] = $paramName;
                        }

                    }
                }


                throw new \Exception(
                    'INVALID RPC.PARAMS: ['
                        . ' Missing: ' . $numParamsMissing
                        . ' (' . implode(', ', $paramNamesMissing) . ')'
                        . ' Expected: ' . $numParamsExpected
                        . ' (' . implode(', ', $paramNames) . ')'
                        . ' Required: ' . $numParamsRequired
                        . ' (' . implode(', ', $paramNamesRequired) . ')'
                        . ' Optional: ' . $numParamsOptional
                        . ' (' . implode(', ', $paramNamesOptional) . ')'
                        . ' Given: ' . $numParamsGiven
                        . ' (' . implode(', ', $paramNamesGiven) . ')'
                        . ']'
                );

            }


        }


        /**
         * @param \ReflectionMethod $reflectionMethod
         * @param ServiceInfo $serviceInfo
         * @throws \Exception
         */
        protected function _validateServiceMethod(
            \ReflectionMethod $reflectionMethod,
            ServiceInfo $serviceInfo
        ) {
            $methodName = $reflectionMethod->getName();

            $allowMethods = (array)$serviceInfo->getClassMethodFilterAllow();
            $denyMethods = (array)$serviceInfo->getClassMethodFilterDeny();
            if (defined(FNM_CASEFOLD)) {
                define('FNM_CASEFOLD', 16);
            }

            $isMatched = false;
            foreach ($allowMethods as $pattern) {
                $isMatched = fnmatch(
                    '' . $pattern,
                    $methodName,
                    FNM_CASEFOLD
                );
                if ($isMatched) {

                    break;
                }
            }
            $isAllowed = ($isMatched === true);
            if (!$isAllowed) {
                throw new \Exception(
                    'INVALID RPC.METHOD: ACCESS DENIED (ISV000F1) '
                );
            }

            $isMatched = false;
            foreach ($denyMethods as $pattern) {
                $isMatched = fnmatch(
                    '' . $pattern,
                    $methodName,
                    FNM_CASEFOLD
                );
                if ($isMatched) {

                    break;
                }
            }
            $isDenied = ($isMatched === true);
            if ($isDenied) {
                throw new \Exception(
                    'INVALID RPC.METHOD: ACCESS DENIED (ISV000F2) '
                );
            }

            if (strpos($methodName, '-') !== false) {

                throw new \Exception(
                    'INVALID RPC.METHOD: ACCESS DENIED (ISV000R1) '
                );

            }
            if (!$reflectionMethod->isPublic()) {

                throw new \Exception(
                    'INVALID RPC.METHOD : ACCESS DENIED (ISV000R2) '
                );
            }
            if ($reflectionMethod->isStatic()) {

                throw new \Exception(
                    'INVALID RPC.METHOD : ACCESS DENIED (ISV000R3) '
                );
            }

        }

        /**
         * @param $rpcMethod string
         * @return ServiceInfo
         */
        protected function _getServiceInfoByRpcMethod($rpcMethod)
        {
            $rpcMethodParsed = $this->_parseRpcMethod($rpcMethod);

            $rpcQualifiedClassName =
                $rpcMethodParsed['rpcQualifiedClassName'];

            $serviceInfo = new ServiceInfo();
            $serviceInfo->setServiceName($rpcQualifiedClassName);
            $servicesDictionaryKey = '' . $serviceInfo->getServiceUid();
            unset($serviceInfo);

            $servicesDictionary = $this->getServiceInfoCache();

            $serviceInfo = null;
            if (array_key_exists(
                $servicesDictionaryKey,
                $servicesDictionary
            )
            ) {
                $serviceInfo = $servicesDictionary[$servicesDictionaryKey];
            }

            if (!($serviceInfo instanceof ServiceInfo)) {
                $serviceInfo = new ServiceInfo();
            }

            return $serviceInfo;

        }

        /**
         * @param $rpcMethod string
         * @return string
         */
        protected function _getServiceMethodNameByRpcMethod($rpcMethod)
        {
            $rpcMethodParsed = $this->_parseRpcMethod($rpcMethod);
            $serviceClassMethodName = $rpcMethodParsed['rpcMethodName'];

            return '' . $serviceClassMethodName;
        }


        /**
         * @param RPC $rpc
         * @param Service $service
         * @param \ReflectionMethod $reflectionMethod
         * @param array $params
         */
        protected function _invokeServiceMethod(
            RPC $rpc,
            Service $service,
            \ReflectionMethod $reflectionMethod,
            array $params = array()
        ) {

            $serviceResult = $reflectionMethod->invokeArgs(
                $service,
                $params
            );

            $rpc->getResponse()
                ->setResult($serviceResult);

        }


        /**
         * @param RPC $rpc
         */
        protected function _onRpcResult(RPC $rpc)
        {
            // do fancy stuff here
        }

        /**
         * @param RPC $rpc
         */
        protected function _onRpcException(RPC $rpc)
        {
            // do fancy stuff here
        }


        /**
         * @param $rpcMethod
         * @return array
         */
        protected function _parseRpcMethod($rpcMethod)
        {
            $rpcMethod = '' . strtolower(trim('' . $rpcMethod));

            $parts = (array)explode('.', $rpcMethod);
            $_parts = array();
            foreach ($parts as $part) {
                $part = '' . ucfirst(trim('' . $part));
                $_parts[] = $part;
            }
            $parts = $_parts;

            $rpcMethodName = '' . strtolower('' . array_pop($parts));
            $rpcClassName = '' . array_pop($parts);
            $rpcPackageName = '' . implode('.', $parts);
            $rpcQualifiedClassName = '' . implode(
                '.',
                array(
                    $rpcPackageName,
                    $rpcClassName,
                )
            );

            $result = array(
                'rpcMethod' => $rpcMethod,
                'rpcPackageName' => $rpcPackageName,
                'rpcClassName' => $rpcClassName,
                'rpcMethodName' => $rpcMethodName,
                'rpcQualifiedClassName' => $rpcQualifiedClassName,
            );

            return $result;

        }



}
