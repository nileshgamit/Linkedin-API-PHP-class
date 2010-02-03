<?php

 require_once("linkedin.class.php");

 $linkedin = new linkedin();
 $linkedin->init(); # not needed for public profile call.
 $linkedin->get_public_profile_by_public_url('http://www.linkedin.com/in/nileshgamit');

?>
