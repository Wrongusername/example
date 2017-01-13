<?php

namespace common\components;
use common\components\AbstractSearchRequest;

interface iServiceSearch
{
    static function findOffers($request);
    static function _toCache($request, array $offers);
}