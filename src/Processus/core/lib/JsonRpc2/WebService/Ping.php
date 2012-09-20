<?php

namespace Processus\Lib\JsonRpc2\WebService;
{
    class Ping extends \Processus\Lib\JsonRpc2\Service
    {

        /**
         * @return bool
         */
        public function test()
        {

            return true;
        }

        /**
         * @return array
         */
        public function error()
        {

            throw new \Exception(
                'Sample error from '
                    . get_class($this)
                    . ' ' .__METHOD__
            );

        }

        /**
         * @param $msg mixed
         * @return mixed
         */
        public function say($msg)
        {
            return $msg;
        }


    }
}

