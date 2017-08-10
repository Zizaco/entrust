<?php echo '<?php' ?>

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends EntrustMenu
{

    protected $casts = [ 'is_active' ];

}
