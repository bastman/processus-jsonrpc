<?php

namespace Processus\Lib\JsonRpc2;

    class Rpc
    {

        /**
         * @var $request Request
         */
        protected $_request;
        /**
         * @var $response Response
         */
        protected $_response;


        /**
         * @return Request
         */
        public function newRequest()
        {
            $request = new Request();

            return $request;
        }

        /**
         * @return Response
         */
        public function newResponse()
        {
            $response = new Response();

            return $response;
        }

        /**
         * @return Request
         */
        public function getRequest()
        {
            if (!($this->_request instanceof Request)) {
                $this->_request = $this->newRequest();
            }

            return $this->_request;
        }

        /**
         * @return Response
         */
        public function getResponse()
        {
            if (!($this->_response instanceof Response)) {
                $this->_response = $this->newResponse();
            }

            return $this->_response;
        }

        /**
         * @param Request $request
         * @return Rpc
         */
        public function setRequest(Request $request)
        {
            $this->_request = $request;

            return $this;
        }

        /**
         * @param Response $response
         * @return Rpc
         */
        public function setResponse(Response $response)
        {
            $this->_response = $response;

            return $this;
        }


    }


