<?php echo '<?php' ?>

namespace App;

use Adesr\Entrust\EntrustRole;

class Menu extends EntrustMenu
{

    protected $casts = [ 'is_active' ];

}
