<?php

namespace Processus\Lib\JsonRpc2;

    class Request
    {

        protected $_data = array(
            'id' => null,
            'version' => '',
            'jsonrpc' => '',
            'method' => '',
            'params' => array(),
        );


        /**
         * @param array $data
         * @return Request
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
         * @return Request
         */
        public function setDataKey($key, $value)
        {
            $this->_data[$key] = $value;

            return $this;
        }


        /**
         * @param array $mixin
         * @return Request
         */
        public function mixinData(array $mixin = array())
        {
            foreach ($mixin as $key => $value) {
                $this->_data[$key] = $value;
            }

            return $this;
        }

        /**
         * @return string|int\float|null
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
         * used by v1
         * @return string
         */
        public function getVersion()
        {
            return (string)$this->getDataKey('version');
        }

        /**
         * used by v2
         * @return string
         */
        public function getJsonrpc()
        {
            return (string)$this->getDataKey('jsonrpc');
        }


        /**
         * @return string
         */
        public function getMethod()
        {
            return (string)$this->getDataKey('method');
        }

        /**
         * @return array
         */
        public function getParams()
        {
            return (array)$this->getDataKey('params');
        }


    }


