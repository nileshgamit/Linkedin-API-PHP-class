<?php

define('CALLBACK_URL', 'http://prj.lamp-technologies.com/developer/linkedin/index.php?_api=accesstoken');
define('BASE_API_URL', 'https://api.linkedin.com');

define('REQUEST_PATH', '/uas/oauth/requestToken');
define('AUTH_PATH', '/uas/oauth/authorize');
define('ACC_PATH', '/uas/oauth/accessToken');

define('CUSTOMER_KEY', '');
define('CUSTOMER_SECRET', '');

$profileFields = array(
	'id', 
	'first-name', 
	'last-name', 
	'picture-url',
	'public-profile-url',
	'headline', 
	'current-status', 
	'location', 
	'distance', 
	'summary',
	'industry', 
	'specialties',
	'positions',
	'educations'
);

?>
