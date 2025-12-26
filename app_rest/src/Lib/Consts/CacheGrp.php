<?php

declare(strict_types = 1);

namespace App\Lib\Consts;

class CacheGrp
{
    const DEFAULT = 'default';
    const ACL = 'acl';
    const UPLOAD = 'upload';
    const SHORT = 'short';
    const EXTRALONG = 'extralong';
    const CORE = '_cake_translations_'; // before cakephp 5.1.0 _cake_core_
    const MODEL = '_cake_model_';
    const ROUTES = '_cake_routes_';
}
