<?php

use Cleantalk\USP\Common\State;
use Cleantalk\USP\Uniforce\FireWall;
use Cleantalk\USP\Common\Err;
use Cleantalk\USP\Common\File;
use Cleantalk\USP\Uniforce\Helper;
use Cleantalk\USP\Variables\Server;

function uniforce_sfw_update( $immediate = false ){
	
	$usp = State::getInstance();
	
	// SFW actions
	if( $usp->key && $usp->settings->fw ){

		// Update SFW
		Helper::http__request(
			Server::get('HTTP_HOST') . CT_USP_AJAX_URI,
			array(
				'spbc_remote_call_token'  => md5( $usp->settings->key ),
				'spbc_remote_call_action' => 'fw__update',
				'plugin_name'             => 'security',
				'file_urls'               => '',
			),
			'get async'
		);
	}

	return ! Err::check() ? true : false;
}

function uniforce_fw_send_logs(){

	$usp = State::getInstance();
	
	// SFW actions
	if( $usp->key && $usp->settings->fw ){

		// Send SFW logs
		$result = FireWall::logs__send( $usp->key );
		
		if( ! empty( $result['error'] ) )
			Err::add( $result['error'] );
		
		if( ! Err::check() ) {
			$usp->data->stat->fw->logs_sent_time = time();
			$usp->data->stat->fw->count = 0;
        }

	}
	
	return ! Err::check() ? true : false;
}

function uniforce_security_send_logs(){

	$usp = State::getInstance();

    // SFW actions
    if( $usp->key && $usp->settings->bfp ){

        // Send SFW logs
        $result = FireWall::security__logs__send( $usp->key );

        if( ! empty( $result['error'] ) )
            Err::add( $result['error'] );

        if( ! Err::check() ) {
	        $usp->data->stat->bfp->logs_sent_time = time();
	        $usp->data->stat->bfp->count = $result;
        }

    }

    return ! Err::check() ? true : false;
}

function uniforce_clean_black_lists() {

    // Remove entries older than 1 hour
    $black_list = CT_USP_ROOT . 'data/bfp_blacklist.php';
    $fast_black_list = CT_USP_ROOT . 'data/bfp_fast_blacklist.php';

    // Black list clean
    if ( file_exists($black_list) ) {
        require_once $black_list;
        $need_to_rewrite = false;
        if ( isset( $bad_ips ) ) {
            if( ! empty( $bad_ips ) ) {
                foreach( $bad_ips as $bad_ip => $bad_ip_added ) {
                    if( $bad_ip_added + 3600 < time() ) {
                        unset( $bad_ips[$bad_ip] );
                        $need_to_rewrite = true;
                    }
                }
            }
        }
        if( $need_to_rewrite ) {
            File::replace__variable( $black_list, 'bad_ips', $bad_ips );
        }
    }

    // Fast black list clean
    if ( file_exists($fast_black_list) ) {
        require_once $fast_black_list;
        $need_to_rewrite = false;
        if ( isset( $fast_bad_ips ) ) {
            if( ! empty( $fast_bad_ips ) ) {
                foreach( $fast_bad_ips as $fast_bad_ip => $fast_bad_ip_info ) {
                    if( $fast_bad_ip_info['added'] + 3600 < time() ) {
                        unset( $fast_bad_ips[$fast_bad_ip] );
                        $need_to_rewrite = true;
                    }
                }
            }
        }
        if( $need_to_rewrite ) {
            File::replace__variable( $fast_black_list, 'bad_ips', $bad_ips );
        }
    }

}

/**
 * Function for cron
 * That launches background scanning
 *
 * @return bool
 */
function usp_scanner__launch(){

	$usp = State::getInstance();

	if ( $usp->scanner_status === false || ! $usp->settings->scanner_auto_start )
		return true;

	return Helper::http__request(
		CT_USP_AJAX_URI,
		array(
			'plugin_name' => 'security',
			'spbc_remote_call_token' => md5($usp->settings->key),
			'spbc_remote_call_action' => 'scanner__controller',
			'state'                   => 'get_hashes'
		),
		'get async'
	);
}

function usp_scanner__get_signatures() {

	$out = \Cleantalk\USP\Scanner\Controller::action__scanner__get_signatures();

	return empty($result['error']) ? $out : true;
}