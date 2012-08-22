<?php

class Cartthrob_pin_net extends Cartthrob_payment_gateway
{
	public $title = 'pin_net_title';
	public $affiliate = '';
	public $overview = 'pin_net_overview';
	
	public $settings = array(
		array(
			'name' => 'mode', 
			'short_name' => 'mode', 
			'type'	=> 'select',
			'default'	=> 'test',
			'options' => array(
				'test'	=> 'test',
				'live' => 'live',
			),
		),
		array(
			'name' => 'pin_net_test_secret_api_key',
			'short_name' => 'test_secret_api_key',
			'type' => 'text',
		),
		array(
			'name' => 'pin_net_test_publishable_api_key',
			'short_name' => 'test_publishable_api_key',
			'type' => 'text',
		),
		array(
			'name' => 'pin_net_live_secret_api_key',
			'short_name' => 'live_secret_api_key',
			'type' => 'text',
		),
		array(
			'name' => 'pin_net_live_publishable_api_key',
			'short_name' => 'live_publishable_api_key',
			'type' => 'text',
		),
	);

	public $fields = array(
		'first_name',
		'last_name',
		'address',
		'address2',
		'city',
		'state',
		'zip',
		'phone',
		'email_address',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_address',
		'shipping_address2',
		'shipping_city',
		'shipping_state',
		'shipping_zip',
		'credit_card_number',
		'CVV2',
		'expiration_year',
		'expiration_month',
 	);
	
	public function process_payment($credit_card_number)
	{
		$key = $this->plugin_settings('mode') === 'live' ? $this->plugin_settings('live_secret_api_key') : $this->plugin_settings('test_secret_api_key');
		
		$host = $this->plugin_settings('mode') === 'live' ? 'https://api.pin.net.au/1/charges' : 'https://test-api.pin.net.au/1/charges';

		$data = array(
			'amount' => $this->total() * 100,
			'description' => $this->order('title'),
			'email' => $this->order('email_address'),
			'ip_address' => $this->order('ip_address'),
			'card' => array(
				'number' => $credit_card_number,
				'expiry_month' => $this->order('expiration_month'),
				'expiry_year' => $this->order('expiration_year'),
				'cvc' => $this->order('CVV2'),
				'name' => $this->order('first_name').' '.$this->order('last_name'),
				'address_line1' => $this->order('address'),
				'address_line2' => $this->order('address2'),
				'address_city' => $this->order('city'),
				'address_postcode' => $this->order('zip'),
				'address_state' => $this->order('state'),
				'address_country' => $this->alpha2_country_code($this->order('country_code')),
			),
		);
		
		$connect = $this->curl_transaction($host, $this->data_array_to_string($data), FALSE, 'POST', FALSE, array(CURLOPT_USERPWD => $key.':')); 

		if ( ! $connect)
		{
			return array(
				'authorized' => FALSE,
				'failed' => TRUE,
				'declined' => FALSE,
				'error_message' => $this->lang('cant_connect'),
				'transaction_id' => '',
			);
		}

		if ( ! $payload = json_decode($connect))
		{
			return array(
				'authorized' => FALSE,
				'failed' => TRUE,
				'declined' => FALSE,
				'error_message' => $this->lang('invalid_response'),
				'transaction_id' => '',
			);
		}

		if ( ! isset($payload->response))
		{
			$message = $this->lang('invalid_response');

			if (isset($payload->messages))
			{
				$message = '';

				foreach ($payload->messages as $m)
				{
					$message .= $message ? ', '.$m->message : $m->message;
				}
			}

			return array(
				'authorized' => FALSE,
				'failed' => TRUE,
				'declined' => FALSE,
				'error_message' => $message,
				'transaction_id' => '',
			);
		}

		if ( ! $payload->response->success)
		{
			return array(
				'authorized' => FALSE,
				'failed' => FALSE,
				'declined' => TRUE,
				'error_message' => $payload->response->error_message,
				'transaction_id' => $payload->response->token,
			);
		}

		return array(
			'authorized' => TRUE,
			'failed' => FALSE,
			'declined' => FALSE,
			'error_message' => '',
			'transaction_id' => $payload->response->token,
		);
	}
}

/* End of file Cartthrob_pin_net.php */
/* Location: ./system/expressionengine/third_party/cartthrob/payment_gateways/Cartthrob_pin_net.php */