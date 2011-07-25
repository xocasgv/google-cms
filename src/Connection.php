<?php
class Connection {
	private $curl;
	private $login;
	private $password;
	private $authString;
	private $headers;
	
	// Initialize the curl object
	// --------------------------------------
	public function __construct($loginARG, $passwordARG, $authStringARG) {
		$this->login = (string) $loginARG;
		$this->password = (string) $passwordARG;
		$this->authString = (string) $authStringARG;
		$this->curl = curl_init();
		// Include the Auth string in the headers
		// Together with the API version being used
		$this->headers = array(
		    "Authorization: GoogleLogin auth=" . $this->authString,
		    "GData-Version: 3.0",
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($this->curl, CURLOPT_POST, false);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
	}
	
	// Perform an authentified http GET request to the given url
	// if response is "Token invalid" refresh the authString() make the request again
	// --------------------------------------
	public function getRequest($url) {
		// Make the request
		curl_setopt($this->curl, CURLOPT_URL, $url);
		$response = curl_exec($this->curl);
		
		// If the authString is out of date (each 2 weeks)  strlen($response) < 1000 and 
		if (strpos($response, "<H1>Token invalid</H1>") or strpos($response, "<H1>Token expired</H1>")) {
			// Get another one and make the request a second time
			$this->refreshAuthString();
			curl_setopt($this->curl, CURLOPT_URL, $url);
			$response = curl_exec($this->curl);
			// If we stil get an error, display "Login failed" and die
			if (strpos($response, "<H1>Token invalid</H1>") or strpos($response, "<H1>Token expired</H1>")) {
				echo "<H1>Login failed</H1><BR>".$response;
				exit(-1);
				// Be carefull, after a few attempts google ask for captcha (not supported)
			}
		}
		if($response == "") {
			return curl_getinfo($this->curl, CURLINFO_HTTP_CODE); // ex: 404
		} else {
			return $response;
		}
	}
	
	// Return true if the page has been modified
	// --------------------------------------
	public function checkForUpdates($url, $etag) {
		$this->headers[] = 'If-None-Match: '.$etag;
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
		return $this->getRequest($url);
	}


	// Get a new AuthString using Google login/password
	// --------------------------------------
	private function refreshAuthString() {
		$tempCurl = curl_init();
		$clientloginUrl = "https://www.google.com/accounts/ClientLogin";
		$clientloginPost = array(
		    "accountType" => "HOSTED_OR_GOOGLE",
		    "Email" => $this->login,
		    "Passwd" => $this->password,
		    "service" => "writely", // the "Google Documents List Data AP" service name
		    "source" => "GoogleCms 3beta"
		);
		curl_setopt($tempCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($tempCurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($tempCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($tempCurl, CURLOPT_URL, $clientloginUrl);
		curl_setopt($tempCurl, CURLOPT_POST, true);
		curl_setopt($tempCurl, CURLOPT_POSTFIELDS, $clientloginPost);

		$response = curl_exec($tempCurl);
		curl_close($tempCurl);

		preg_match("/Auth=([a-z0-9_\-]+)/i", $response, $matches);
		$this->authString = $matches[1];
		$this->headers = array(
		    "Authorization: GoogleLogin auth=" . $this->authString,
		    "GData-Version: 3.0",
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
	}
	
	public function close() {
		curl_close($this->curl);
	}

	public function getAuthString() {
		return $this->authString;
	}
}
?>