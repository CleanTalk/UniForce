<?php

session_start();

use Cleantalk\Variables\Session;

require_once 'inc' . DIRECTORY_SEPARATOR . 'common.php';  // Common stuff
require_once 'inc' . DIRECTORY_SEPARATOR . 'actions.php'; // Actions

// URL ROUTING
switch (true){
	// Installation
	case empty( $uniforce_is_installed ):
		$page = 'install';
		break;
	// Login
	case Session::get('authenticated') !== 'true':
		$page = 'login';
        break;
    // Settings
    case Session::get('authenticated') === 'true':
	    $page = 'settings';
        break;
}

// Common script for all pages
require_once CT_USP_VIEW . 'header.php';

// Page content
require_once CT_USP_VIEW . $page . '.php';

// Footer
require_once CT_USP_VIEW . 'footer.php';