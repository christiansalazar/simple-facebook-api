<?php
/**
 * SimpleFacebookApi
 *	Yeap, another simple way to get access into facebook API without using
 *	all stupid stuff from the full official api.
 *
 *  So simple: add a post to users wall or page no more, no less.
 *
 *	Usage is VERY simple:
 *
 *		$api = new SimpleFacebookApi;
 *		$api->setConfig(array("appid", "apikey", "a shortlive accesstoken"));
 *		$api->addPostToPage("a_pageid",array("message"=>"helo","link"=>".."));
 * 
 *	About Access Tokens:
 *
 *		All access tokens are shortlived (2 days), but, facebook help us in
 *		extends it by using the method provided in this class. So, the first
 *		time you use this class provide a shortlive accesstoken.
 *
 * @author Cristian Salazar H. <christiansalazarh@gmail.com> @salazarchris74 
 * @license FreeBSD {@link http://www.freebsd.org/copyright/freebsd-license.html}
 */
class SimpleFacebookApi {
	private $cacheid='FacebookPublisher';
	private $last_url_used;
	private $_verbose;
	private $_lastresponse;
	private $_lasterrorcode;
	private $config;

	// interface
	public function getLastError(){
		return $this->_lasterrorcode;
	}
	// interface
	function addPostToPage($page_id, $message,$verbose=true){
		// post a message into the post list of a given page.
		// $message = array("message"=>"some text", "link"=>"some link")
		//
		// IMPORTANT:
		//	SET: the scope 'publish_actions' must be queried
		//		see also FacebookLogin wordpress plugin / "scope" argument
		//  SET: make this application "public" or "friends".
		//      in your settings/applications
		//
		$url = sprintf("https://graph.facebook.com/v2.1/%s/feed",$page_id);
		return $this->send($this->getConfig(), $url, $message, $verbose);
	}
	// interface
	function addPostToWall($message,$verbose=true){
		// post a message into the user (or page) wall
		// $message = array("to"=>"user or page id","message"=>"some text", "link"=>"some link")
		$url = sprintf("https://graph.facebook.com/v2.1/me/feed");
		return $this->send($this->getConfig(), $url,$message, $verbose);
	}
	// interface
	public function setConfig($c){
		// array("appid","apikey","shortlived access token")
		list($appid, $appsecret, $accesstoken) = $c;
		$this->cacheid = "fblogin-".$appid;
		$this->config = $c;
	}
	private function getConfig(){
		return $this->config;
	}
	protected function send($config, $service_url, $message, $verbose = true){
		$this->_verbose=$verbose;
		if($verbose){
			printf("try pop accesstoken:\n");
			print_r($this->popAccessToken());
			printf("\n");
		}
		if(!($access_token = 
			$this->getParam($this->popAccessToken(), "access_token"))){
			// setup time: the first time delete the cached file
			// and provide a short-live access token via config argument.
			if($response = $this->getNewLongLiveAccessToken($config)){
				$access_token = $this->getParam($response, "access_token");
				$expires = $this->getParam($response, "expires");
				if($verbose)
					printf("new token obtained. expires: %s\n",
						date("Y-m-d H:i:s T",time()+$expires));
				$this->pushAccessToken($response);
			}else{
				$this->clearCache();
				if($verbose){
					printf("\nlast error was:\n%s\n",$this->_lasterrorcode);
					printf("\nlast response was:\n%s\n",$this->_lastresponse);
				}
				die("can't create a long live access token.\nprovide a new short-live access token in config.\n");
			}
		}
		if($result = $this->makePostRequest($service_url,
			array("access_token"=>$access_token),$message)){
			// success
			return $result;
		}else
		return null;
	}
	private function getNewLongLiveAccessToken($config){
		list($app_id, $app_secret, $some_valid_token) = $config;
		$q = array(
			"client_id"=>$app_id,
			"client_secret"=>$app_secret,
			"fb_exchange_token"=>$some_valid_token,
			"grant_type"=>"fb_exchange_token",
		);
		$r = $this->makeRequest(
			"https://graph.facebook.com/oauth/access_token",$q);
		return (0 == $this->_lasterrorcode) ? $r : null;
	}
	// auxiliar:
	private function makeRequest($url, $params, $ch=null) {
		if (!$ch)	$ch = curl_init();
		$q = http_build_query($params, null, '&');
		$opts[CURLOPT_URL] = $url."?".$q;
		$this->last_url_used = $url."?".$q;
		if($this->_verbose) printf("\t%s\n",$this->last_url_used);
		curl_setopt_array($ch, $opts);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$this->_lastresponse = curl_exec($ch);
		$this->_lasterrorcode = curl_errno($ch);
		return $this->_lastresponse;
	}
	private function makePostRequest($url, $params, $post) {
		$ch = curl_init();
		$q = http_build_query($params, null, '&');
		$this->last_url_used = $url."?".$q;
		curl_setopt($ch, CURLOPT_URL,$url."?".$q);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		return $result;
	}
	private function getParam($from, $name){
		if(!$from) return "";
		foreach(@explode("&",$from) as $item){
			$k = @explode("=",$item);
			if($name == $k[0])
				return $k[1];
		}
		return "";
	}
	private function getCacheFileName(){
		return sprintf("/var/tmp/%s.fb.accesstoken",$this->cacheid);
	}
	private function popAccessToken(){
		$at="";
		$f = @fopen($this->getCacheFileName(),"r");
		if($f){
			$at = fread($f,512); 
			fclose($f);
		}
		return $at;
	}
	private function pushAccessToken($access_token){
		$f = @fopen($this->getCacheFileName(),"w");
		if($f){
			fwrite($f, $access_token); 
			fclose($f);
		}
		return $access_token;
	}
	private function clearCache(){
		@unlink($this->getCacheFileName());
	}
}
