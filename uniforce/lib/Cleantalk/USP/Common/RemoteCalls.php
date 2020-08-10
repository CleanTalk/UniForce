<?php

namespace Cleantalk\USP\Common;

use Cleantalk\USP\Variables\Get;

class RemoteCalls
{

	const COOLDOWN = 10;

	public static function check() {
		
		return Get::is_set('spbc_remote_call_token', 'spbc_remote_call_action', 'plugin_name') &&
		       in_array(Get::get('plugin_name'), array('security','spbc'));
	}

	public static function perform(){
		
		$usp = State::getInstance();

		$action = strtolower(Get::get('spbc_remote_call_action'));
		$token  = strtolower(Get::get('spbc_remote_call_token'));

		if ( isset( $usp->remote_calls->$action ) ) {

			$cooldown = isset($usp->remote_calls->$action->cooldown)
				? $usp->remote_calls->$action->cooldown
				: self::COOLDOWN;
			$pass_cooldown = Helper::ip__get(array('real')) === filter_input(INPUT_SERVER, 'SERVER_ADDR');

			if(time() - $usp->remote_calls->$action->last_call >= $cooldown
				 || $pass_cooldown
			){

				$usp->remote_calls->$action->last_call = time();
				$usp->remote_calls->save();

				// Check API key
				if($token == strtolower(md5($usp->settings->key)) ){

					$action = 'action__'.$action;

					// Scanner actions
					if ( strpos( $action, 'scanner__' ) !== false ) {
						
						if ( method_exists( '\Cleantalk\USP\ScannerController', $action ) ) {
								
								$scanner_controller = new \Cleantalk\USP\ScannerController(
									CT_USP_SITE_ROOT,
									array( $usp->data->db_request_string, $usp->data->db_user, $usp->data->db_password)
								);
								$out = $scanner_controller->$action();
						}

					// Common actions
					}else if(method_exists('\Cleantalk\USP\Common\RemoteCalls', $action)){

						sleep( (int) Get::get('delay') ); // Delay before perform action;
						$out = RemoteCalls::$action();

					}else
						Err::add('UNKNOWN_ACTION_METHOD');
				}else
					Err::add('WRONG_TOKEN');
			}else
				Err::add('TOO_MANY_ATTEMPTS');
		}else
			Err::add('UNKNOWN_ACTION');

		die( Err::check()
			? Err::check_and_output( 'as_json' )
			: json_encode($out)
		);
	}

	static function action__close_renew_banner() {

		$usp = State::getInstance();
		$usp->data->notice_show = 0;
		$usp->data->save();

		die('OK');
	}

	static function action__fw__update() {
		
		$result = \Cleantalk\USP\Uniforce\FireWall::action__fw__update(
			State::getInstance()->key
		);
		
		die(empty($result['error']) ? 'OK' : 'FAIL '.json_encode(array('error' => $result['error'])));
	}
}