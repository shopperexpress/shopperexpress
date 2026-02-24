<?php
/**
 * Evox
 */
class Evox
{

	public $VIFID;
	public $ProductID;
	public $ProductTypeID;

	public function __construct( $VIFID, $ProductID, $ProductTypeID )
	{
		$this->VIFID = $VIFID;
		$this->ProductID = $ProductID;
		$this->ProductTypeID = $ProductTypeID;
		$this->api_key = get_field( 'evox_api_key', 'options' );

	}

	public function get_images(){

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://api.evoximages.com/api/v1/vehicles/' . $this->VIFID . '/products/' . $this->ProductID . '/' . $this->ProductTypeID . '/?api_key=' . $this->api_key);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		$result = json_decode( $result );
		
		return $result->urls;
	}
}