<?php
require_once 'Doctrine.php';

set_include_path(realpath(dirname(__FILE__) . '/../src/') . PATH_SEPARATOR . get_include_path());

spl_autoload_register(array('Doctrine', 'autoload'));

