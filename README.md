simple-facebook-api
===================

a very simple API to gain access to the facebook REST library. php based. no namespaces.

#USAGE

 	$api = new SimpleFacebookApi;
	$api->setConfig(array("appid", "apikey", "a shortlive accesstoken"));
	$api->addPostToPage("a_pageid",array("message"=>"helo","link"=>".."));
	$api->addPostToWall(array("to"=$user_id, "message"=>"helo","link"=>".."));

#VERY IMPORTANT

	1. Facebook OpenGraph Explorer is your best friend.
	2. Google is your best friend too.
	3. You must create an Application in https://developer.facebook.com
	
