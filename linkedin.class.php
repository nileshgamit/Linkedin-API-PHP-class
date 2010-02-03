<?php
/* Copyright (C) 2010 Nilesh <nilesh.gamit@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * or see http://www.gnu.org/
 */

session_start();
require_once("oauth.php");
require_once("config.php");

class linkedin {

	protected $signatureMethod;
	protected $consumer;

	protected $_id;
	protected $_firstname;
	protected $_lastname;
	protected $_pictureURL;
	protected $_publicURL;
	protected $_headline;
	protected $_currentStatus;
	protected $_locationName;
	protected $_locationCountryCode;
	protected $_distance;
	protected $_summary;
	protected $_industry;

	protected $_specialties = array();
	protected $_positions = array();
	protected $_eductions = array();
	protected $_connections = array();

	protected $_public = true;
	
	function __construct() { }

	function get_curl_response($toHeader, $url, $post = true) {

	        $ch = curl_init();
	
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	        curl_setopt($ch, CURLOPT_HTTPHEADER, array($toHeader));
	        curl_setopt($ch, CURLOPT_URL, $url);

		if($post) { 
		   curl_setopt($ch, CURLOPT_POSTFIELDS, '');
		   curl_setopt($ch, CURLOPT_POST, 1); 
		}

		$output = curl_exec($ch);
  		curl_close($ch);

		return $output;
  	}

	function init() {

		$this->_public = false;

		$this->signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer(CUSTOMER_KEY, CUSTOMER_SECRET, NULL);
	
		if(!isset($_GET['_api'])) {
			$this->get_request_token();
 	  	}
		else {
			$this->get_access_token();
 		}
   	}

	function get_request_token() {

	        $reqObj = OAuthRequest::from_consumer_and_token($this->consumer, NULL, "POST", BASE_API_URL.REQUEST_PATH);
	        $reqObj->set_parameter("oauth_callback", CALLBACK_URL); # part of OAuth 1.0a - callback now in requestToken
	        $reqObj->sign_request($this->signatureMethod, $this->consumer, NULL);
		$toHeader = $reqObj->to_header();
	
		$output = $this->get_curl_response($toHeader, BASE_API_URL.REQUEST_PATH);
		parse_str($output, $oauth);

	        $_SESSION['oauth_token'] = $oauth['oauth_token'];
	        $_SESSION['oauth_token_secret'] = $oauth['oauth_token_secret'];

		header('Location: ' . BASE_API_URL . AUTH_PATH . '?oauth_token=' . $oauth['oauth_token']);
	}

	function get_access_token() {

	        $token = new OAuthConsumer($_REQUEST['oauth_token'], $_SESSION['oauth_token_secret'], 1);

	        $accObj = OAuthRequest::from_consumer_and_token($this->consumer, $token, "POST", BASE_API_URL.ACC_PATH);
	        $accObj->set_parameter("oauth_verifier", $_REQUEST['oauth_verifier']); # need the verifier too!
	        $accObj->sign_request($this->signatureMethod, $this->consumer, $token);
		$toHeader = $accObj->to_header();

		$output = $this->get_curl_response($toHeader, BASE_API_URL.ACC_PATH);
		parse_str($output, $oauth);

	        $_SESSION['oauth_token'] = $oauth['oauth_token'];
	        $_SESSION['oauth_token_secret'] = $oauth['oauth_token_secret'];
	}

	function get_profile($requestURL) {

		Global $profileFields;

		$endpoint = $requestURL.":(".join(',', $profileFields).")";
        
		$token = new OAuthConsumer($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], 1);
        
		$profileObj = OAuthRequest::from_consumer_and_token($this->consumer, $token, "GET", $endpoint, array());
	        $profileObj->sign_request($this->signatureMethod, $this->consumer, $token);
		$toHeader = $profileObj->to_header();

		$this->parse_xml($this->get_curl_response($toHeader, $endpoint, false));
	}

	function get_logged_in_users_profile() {
		$this->get_profile(BASE_API_URL . '/v1/people/~');
	}

	function get_public_profile_by_public_url($publicURL) {

		if($this->_public)
			$this->parse_public_profile($this->get_curl_response(null, $publicURL, false));
		else
			$this->get_profile(BASE_API_URL . '/v1/people/url=' . urlencode($publicURL));
	}

	function get_public_profile_by_member_token($memberToken) {
		$this->get_profile(BASE_API_URL . '/v1/people/id=' . $memberToken);
	}

	function parse_xml($data) {

		$profileXML = simplexml_load_string($data);

		if(isset($profileXML->{'id'}))
			$this->_id = $profileXML->{'id'};

		if(isset($profileXML->{'first-name'}))
			$this->_firstname = $profileXML->{'first-name'};

		if(isset($profileXML->{'last-name'}))
			$this->_lastname = $profileXML->{'last-name'};

		if(isset($profileXML->{'picture-url'}))
			$this->_pictureURL = $profileXML->{'picture-url'};

		if(isset($profileXML->{'public-profile-url'}))
			$this->_publicURL = $profileXML->{'public-profile-url'};

		if(isset($profileXML->headline))
			$this->_headline = $profileXML->headline;

		if(isset($profileXML->{'current-status'}))
			$this->_currentStatus = $profileXML->{'current-status'};
		
		if(isset($profileXML->location->name))
			$this->_locationName = $profileXML->location->name;

		if(isset($profileXML->location->country->code))
			$this->_locationCountryCode = $profileXML->location->country->code;

		if(isset($profileXML->distance))
			$this->_distance = $profileXML->distance;

		if(isset($profileXML->{'summary'}))
			$this->_summary = $profileXML->{'summary'};

		if(isset($profileXML->industry))
			$this->_industry = $profileXML->industry;

	}

	function parse_public_profile($data) {

		preg_match('/<span class=\"given-name\">(.*?)<\/span>/is', $data, $_firstname);
		if(isset($_firstname[1]))
			$this->_firstname = trim($_firstname[1]);

		preg_match('/<span class=\"family-name\">(.*?)<\/span>/is', $data, $_lastname);
		if(isset($_lastname[1]))
			$this->_lastname = trim($_lastname[1]);

		preg_match('/<div class=\"image\"><img src=\"(.*?)\"(.)*\/><\/div>/', $data, $_pictureURL);
		if(isset($_pictureURL[1]))
			$this->_pictureURL = trim($_pictureURL[1]);

		preg_match('/<p class=\"headline title\">(.*?)<\/p>/is', $data, $_headline);
		if(isset($_headline[1]))
			$this->_headline = trim($_headline[1]);

		preg_match('/<p class=\"locality\">(.*?)<\/p>/is', $data, $_locationCountryCode);
		if(isset($_locationCountryCode[1]))
			$this->_locationCountryCode = trim($_locationCountryCode[1]);

		preg_match('/<p class=\"summary\">(.*?)<\/p>/is', $data, $_summary);
		if(isset($_summary[1]))
			$this->_summary = trim($_summary[1]);

		preg_match('/<dt>Industry<\/dt>(.*?)<dd>(.*?)<\/dd>/is', $data, $_industry);
		if(isset($_industry[2]))
			$this->_industry = trim($_industry[2]);

	}

	function get_member_token() { return $this->_id; }

	function get_firstname() { return $this->_firstname; }

	function get_lastname() { return $this->_lastname; }

	function get_picture_url() { return $this->_pictureURL; }

	function get_public_profile_url() { return $this->_publicURL; }

	function get_headline() { return $this->_headline; }

	function get_current_status() { return $this->_currentStatus; }

	function get_location_name() { return $this->_locationName; }

	function get_location_country_code() { return $this->_locationCountryCode; }

	function get_distance() { return $this->_distance; }

	function get_summary() { return $this->_summary; }

	function get_industry() { return $this->_industry; }

}

?>
