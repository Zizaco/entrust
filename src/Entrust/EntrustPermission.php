<?php

namespace Bbatsche\Entrust;

use Bbatsche\Entrust\Contracts\EntrustPermissionInterface;
use Bbatsche\Entrust\Traits\EntrustPermissionTrait;
use Illuminate\Database\Eloquent\Model;

class EntrustPermission extends Model implements EntrustPermissionInterface
{
    use EntrustPermissionTrait;
}
