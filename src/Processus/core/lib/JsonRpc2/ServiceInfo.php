<?php

namespace Processus\Lib\JsonRpc2;

    class ServiceInfo
    {


        /**
         * @var array
         */
        protected $_data = array(

            "serviceName" => "Seb.User",
            "className" => "{{NAMESPACE}}\Service\User",
            'isValidateMethodParamsEnabled' => true,
            "classMethodFilter" => array(
                "allow" => array(
                    "*"
                ),
                "deny" => array(
                    "*myPrivateMethod"
                ),
            ),


        );


        /**
         * @return string
         */
        public function getServiceUid()
        {
            return '' . trim(strtolower('' . $this->getServiceName()));
        }

        /**
         * @param array $data
         * @return ServiceInfo
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
         * @return ServiceInfo
         */
        public function setDataKey($key, $value)
        {
            $this->_data[$key] = $value;

            return $this;
        }


        /**
         * @param array $mixin
         * @return ServiceInfo
         */
        public function mixinData(array $mixin = array())
        {
            foreach ($mixin as $key => $value) {
                $this->_data[$key] = $value;
            }

            return $this;
        }

        /**
         * @return string
         */
        public function getClassName()
        {
            return '' . $this->getDataKey('className');
        }

        /**
         * @return string
         * @return ServiceInfo
         */
        public function setClassName($className)
        {
            $this->setDataKey('className', '' . $className);

            return $this;
        }


        /**
         * @return string
         */
        public function getServiceName()
        {
            return '' . $this->getDataKey('serviceName');
        }

        /**
         * @return string
         * @return ServiceInfo
         */
        public function setServiceName($serviceName)
        {
            $this->setDataKey('serviceName', '' . $serviceName);

            return $this;
        }

        /**
         * @return bool
         */
        public function getIsValidateMethodParamsEnabled()
        {
            return ($this->getDataKey(
                'isValidateMethodParamsEnabled'
            ) === true);
        }

        /**
         * @param $value
         * @return ServiceInfo
         */
        public function setIsValidateMethodParamsEnabled($value)
        {
            $this->setDataKey(
                'isValidateMethodParamsEnabled',
                ($value === true)
            );

            return $this;
        }

        /**
         * @return array
         */
        public function getClassMethodFilter()
        {
            return (array)$this->getDataKey('classMethodFilter');
        }

        /**
         * @param array $filter
         * @return ServiceInfo
         */
        public function setClassMethodFilter(array $filter = array())
        {
            $this->setDataKey('classMethodFilter', $filter);

            return $this;
        }

        /**
         * @return array
         */
        public function getClassMethodFilterKey($filterKey)
        {
            $filterKey = '' . $filterKey;

            $filter = $this->getClassMethodFilter();

            if (!array_key_exists($filterKey, $filter)) {
                $filter[$filterKey] = array();
            }
            if (!is_array($filter[$filterKey])) {
                $filter[$filterKey] = array();
            }

            $this->setClassMethodFilter($filter);

            return $filter[$filterKey];
        }


        /**
         * @return array
         */
        public function getClassMethodFilterAllow()
        {
            $result = $this->getClassMethodFilterKey('allow');

            return $result;
        }

        /**
         * @return array
         */
        public function getClassMethodFilterDeny()
        {
            $result = $this->getClassMethodFilterKey('deny');

            return $result;
        }

    }


