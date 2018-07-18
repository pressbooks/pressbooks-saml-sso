<?php

namespace Pressbooks\Shibboleth;

/**
 * @return \Jenssegers\Blade\Blade
 */
function blade() {
	$views = __DIR__ . '/../templates';
	$cache = \Pressbooks\Utility\get_cache_path();
	$blade = new \Jenssegers\Blade\Blade( $views, $cache, new \Pressbooks\Container() );
	return $blade;
}
