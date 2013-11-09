<?php

return array(

	/**
	 * If you use custom defined namespaces or non-standard model names for
	 * some reason, define them here. You probably want to publish the
	 * package first. Run `php artisan config:publish zizaco/entrust`,
	 * then this config will be in app/config/packages/zizaco/entrust
	 */

	'role' => 'Role',

	'permission' => 'Permission',

	/**
	 * Values which evaluate loosely to false will cause Entrust to fall
	 * through to the user model defined in database.model. This is
	 * usually what you would want to occur.
	 */

	'user' => null
);