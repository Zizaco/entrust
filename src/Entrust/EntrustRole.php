<?php

namespace Bbatsche\Entrust;

use Bbatsche\Entrust\Contracts\EntrustRoleInterface;
use Bbatsche\Entrust\Traits\EntrustRoleTrait;
use Illuminate\Database\Eloquent\Model;

class EntrustRole extends Model implements EntrustRoleInterface
{
    use EntrustRoleTrait;
}
