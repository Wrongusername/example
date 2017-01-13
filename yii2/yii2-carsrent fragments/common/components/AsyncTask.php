<?php
/**
 * Created by PhpStorm.
 * User: Whatever
 * Date: 7/29/2016
 * Time: 10:46 AM
 */

namespace common\components;


class AsyncTask
{

    protected $_pipes;

    public function __construct($pipes)
    {
        $this->_pipes=$pipes;
    }

    public function running($sectimeout=0,$usectimeout=10)
    {
        $n=null;
        $read = $this->_pipes;
        stream_select($read, $n, $n, $sectimeout, $usectimeout);

        $results = stream_get_contents($this->_pipes[0]);

        if(!feof($this->_pipes[0]))
        {
            return true;
        }
        else
        {
            pclose($this->_pipes[0]);
            return false;
        }
    }

}