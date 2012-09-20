<?php

namespace Processus\Lib\JsonRpc2;

    class Response
    {

        /**
         * @var array
         */
        protected $_data = array(
            'id' => null,
            'version' => null,
            'jsonrpc' => null,
            'result' => null,
            'error' => null,
        );

        /**
         * @var \Exception|null
         */
        protected $_exception;

        /**
         * @param array $data
         * @return Response
         */
        public function setData(array $data = array())
        {
            $this->_data = $data;

            return $this;
        }

        /**
         * @param $key
         * @return mixed
         */
        public function getDataKey($key)
        {
            $result = null;

            if (!array_key_exists($key, $this->_data)) {

                return $result;
            }

            return $this->_data[$key];
        }

        /**
         * @param $key
         * @param $value
         * @return Response
         */
        public function setDataKey($key, $value)
        {
            $this->_data[$key] = $value;

            return $this;
        }


        /**
         * @param array $mixin
         * @return Response
         */
        public function mixinData(array $mixin = array())
        {
            foreach ($mixin as $key => $value) {
                $this->_data[$key] = $value;
            }

            return $this;
        }


        /**
         * @return string|int|float|null
         */
        public function getId()
        {
            $result = null;

            $value = $this->getDataKey('id');

            if ($value === null) {

                return $value;
            }
            if (is_string($value)) {

                return $value;
            }
            if (is_int($value)) {

                return $value;
            }
            if (is_float($value)) {

                return $value;
            }

            return $result;

        }

        /**
         * @return string
         */
        public function getVersion()
        {
            return (string)$this->getDataKey('version');
        }

        /**
         * @return string
         */
        public function getJsonrpc()
        {
            return (string)$this->getDataKey('jsonrpc');
        }

        /**
         * @return mixed
         */
        public function getResult()
        {
            return $this->getDataKey('result');
        }

        /**
         * @return array|null
         */
        public function getError()
        {
            $result = null;

            $value = $this->getDataKey('error');
            if (!is_array($value)) {

                return $result;
            }

            return $value;

        }

        /**
         * @param $id int|string|float|null
         * @return Response
         */
        public function setId($id)
        {
            $value = null;

            if (
                ($id === null)
                || (is_string($id))
                || (is_int($id))
                || (is_float($id))
            ) {
                $value = $id;
            }

            $this->setDataKey('id', $value);

            return $this;
        }

        /**
         * @param $version string
         * @return Response
         */
        public function setVersion($version)
        {
            $this->setDataKey('version', '' . $version);

            return $this;
        }

        /**
         * @param $version string
         * @return Response
         */
        public function setJsonrpc($version)
        {
            $this->setDataKey('jsonrpc', '' . $version);

            return $this;
        }

        /**
         * @param $result mixed
         */
        public function setResult($result)
        {
            $this->setDataKey('result', $result);

            return $this;
        }

        /**
         * @param \Exception $error
         * @return Response
         */
        public function setException(\Exception $error)
        {
            $this->_exception = $error;

            $errorData = array(
                'message' => $error->getMessage(),
            );

            $this->setDataKey('error', $errorData);

            return $this;
        }

        /**
         * @return \Exception|null
         */
        public function getException()
        {
            return $this->_exception;
        }

        /**
         * @return bool
         */
        public function hasException()
        {
            return ($this->_exception instanceof \Exception);
        }



}

