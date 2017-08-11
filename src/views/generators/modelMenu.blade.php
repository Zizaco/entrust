<?php echo '<?php' ?>

namespace App;

use Adesr\Entrust\EntrustMenu;

class Menu extends EntrustMenu
{

    protected $casts = [ 'is_active' ];

}
