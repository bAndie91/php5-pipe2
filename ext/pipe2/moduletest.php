<?php
$br = (php_sapi_name() == "cli")? "":"<br>";

$module = 'pipe2';

if(!extension_loaded( $module )) {
	dl($module . '.' . PHP_SHLIB_SUFFIX);
}
$functions = get_extension_funcs($module);
echo "Functions available in the test extension:$br\n";
foreach($functions as $func) {
    echo $func."$br\n";
}
echo "$br\n";
