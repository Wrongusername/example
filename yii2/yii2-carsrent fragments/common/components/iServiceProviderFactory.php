<?php

namespace common\components;
use common\models\lib\Operator;


interface iServiceProviderFactory
{
    public static function getAdapter(Operator $operator);
    public static function getAllAdapters($active);
}