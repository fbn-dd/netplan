<?php
require_once 'HTTP/Client.php';
require_once(dirname(__FILE__)."/db.php");

/**********************************************************************
 * wikitools
 * boolean=wikiLogin(object=client)
 * string=wikiExport(object=client,string=page)
 * array=wikiGetToken(object=client,string=page)
 * boolean=wikiEdit(object=client,array=token,string=page,string=content,array=options)
 *********************************************************************/

define(WIKIDEBUG,false);
//define(WIKIDEBUG,true);
define(WIKIAPI,"https://server.example.org/w/api.php");
define(WIKIEXP,"https://server.example.org/wiki/Spezial:Exportieren/");
define(AP_TEMPL,'{{Accesspoints|
SSID=www.example.org/<$Location><br />www.example.org/[[EAP]] |
ADRESSE=<$Street> |
ORT=<$Postcode> [[:Kategorie:<$Section>|<$City>]] |

ANTENNE= |
KANAL= |
ROUTER= |
IP= |
BESCHREIBUNG=<$Contact> |
ANBINDUNG= |
ABDECKUNG= |
APV= |
GALERIE=}}
');

/* boolean=addWikiKategorie(object=section)
* returns true on success
* function adds Kategorie:AP_$section to the wiki.
* The page will contain 'Nachfolgend sind alle APs aus dem Bereich $section aufgelistet. ----' */

function getUserData() {
	if(is_string($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] != "") {
		$usr = DB_DataObject::factory('Mitglieder');
		$usr->Username = $_SERVER['PHP_AUTH_USER'];
		$usr->find(TRUE);
		$username = ($usr->Username ? $usr->Username : $_SERVER['PHP_AUTH_USER']);
		$passwd = $_SERVER['PHP_AUTH_PW'];
		$myArray=Array($username,$passwd,'BASIC '.base64_encode("$username:$passwd"));
		if(WIKIDEBUG===TRUE) {
			echo "<pre>getUserData()";
			echo "\nreturns:";
			var_dump($myArray);
			echo "</pre><br/>\n";
		}
		return($myArray);
	} else {
		return(FALSE);
	}
}
	
function addWikiKategorie($section) {
	return(FALSE);

	$userdata = getUserData();
	if($userData===FALSE)
	 return(FALSE);
	$c = new HTTP_Client(null,Array('Authorization'=> $userdata[2]));
	$c->setDefaultHeader(Array('Authorization'=> $userdata[2]));
	if(WIKIDEBUG===TRUE) {
		echo "<pre>client:";
		var_dump($c);
		echo "</pre><br/>\n";
	}
	if(wikiLogin($c)===FALSE)
	 return(FALSE);
	$token = wikiGetToken($c,$section['description']);
	if($token===FALSE) {
		wikiLogout($client);
		return(FALSE);
	}
	if(wikiEdit($client,$token,$section['description'],
	 "Nachfolgend sind alle APs aus dem Bereich ".$section['description']." aufgelistet.\n----\n")===FALSE) {
		wikiLogout($client);
		return(FALSE);
	}
	wikiLogout($client);
	return(TRUE);
}

// $section['id_section'] + $section['description']
// $location['id_location'] $location['id_section'] $location['description'] $location['street...postcode...city...]
/* boolean=addWikiAP(object=location)
* returns true on success
* function adds location to the wiki. Uses Vorlage:Accesspoints as template.
* Template will be filled using the parameters from given object */
function addWikiAP($location) {
	return(FALSE);

	$userdata = getUserData();
	if($userData===FALSE)
	 return(FALSE);
	$c = new HTTP_Client(null,Array('Authorization'=> $userdata[2]));
	$c->setDefaultHeader(Array('Authorization'=> $userdata[2]));
	if(WIKIDEBUG===TRUE) {
		echo "<pre>client:";
		var_dump($c);
		echo "</pre><br/>\n";
	}
	if(wikiLogin($c)===FALSE)
	return(FALSE);
/****
	$templ = wikiExport($client,$page);
	if($templ===FALSE) {
		wikiLogout($client);
		return(FALSE);
	}
****/
	$templ = AP_TEMPL;
	$token = wikiGetToken($c,$location['description']);
	if($token===FALSE) {
		wikiLogout($client);
		return(FALSE);
	}
	// Variablen ausfuellen
	$templ = preg_replace(Array(
		'/<\$Section>/',
		'/<\$Location>/',
		'/<\$Street>/',
		'/<\$Postcode>/',
		'/<\$City>/',
		'/<\$Contact>/'),Array(
		'<$Section', //$location[''],
		$location['description'],
		$location['street'],
		$location['postcode'],
		$location['city'],
		$location['contact']),
	 $templ);
	if(wikiAdd($client,$token,$location['description'],$templ)===FALSE) {
		wikiLogout($client);
		return(FALSE);
	}
	wikiLogout($client);
	return(TRUE);
}

/* boolean=wikiLogin(object=client)
 * returns true on success
 * function tries to login the user to the wiki. */
function wikiLogin(&$client) {
	$userdata = getUserData();
	if($userdata !==FALSE) {
		$client->setDefaultHeader(Array('Authorization'=> $userdata[2]));
		if(WIKIDEBUG===TRUE) {
			echo "<pre>client:";
			var_dump($client);
			echo "</pre><br/>\n";
		}
		$postArray = Array(
			'lgname'=>$userdata[0],
			'lgpassword'=>$userdata[1],
			'format'=>'php',
			'action'=>'login'
		);
		$rc = $client->post(WIKIAPI,$postArray);
		$res = $client->currentResponse();
		if(WIKIDEBUG===TRUE) {
			echo "<pre>wikiLogin(client)";
			echo "\npostArray:";
			var_dump($postArray);
			echo "\nrc=$rc";
			echo "\nres:";
			var_dump($res);
			echo "</pre><br/>\n";
		}
		if($rc===200) {
			$result = unserialize($res['body']);
			if($result['login']['result']==='Success')
			 return(TRUE);
		}
	}
	echo "Fehler beim Wiki login. ";
	if(isset($result))
	 echo $result['login']['result'];
	return(FALSE);
}

// TODO: Erkennen, wenn Seite falsch geladen (wiki macht meist umleitung auf Hauptseite bei fehler urls
/* string=wikiExport(object=client,string=page)
 * returns xml string of exported wiki page or false on error
 * requires wikiLogin on most wiki systems before start */
function wikiExport(&$client,$page) {
	$rc = $client->get(WIKIEXP.$page);
	$res = $client->currentResponse();
	if(WIKIDEBUG===TRUE) {
		echo "<pre>wikiExport(client,$page)";
		echo "\nrc=$rc";
		echo "\nres:";
		var_dump($res);
		echo "</pre><br/>\n";
	}
	if($rc===200) {
		/*** xml parse deletes &lt; tags ; thats shit. so we need to pre_replace
		$p = xml_parser_create();
		xml_parse_into_struct($p,$res['body'],$vals,$idx);
		xml_parser_free($p);
		$txt = $vals[$idx['TEXT'][0]]['value'];
		***/
		$txt = $res['body'];
		$txt = preg_replace(Array(
			'!.*<text xml:space="preserve">!s',
			'!</text>.*!s'
		),'',$txt);
		if(WIKIDEBUG===TRUE)
			echo "<pre>txt_before=---start---&gt;$txt&lt;---end---</pre><br/>\n";
//		$txt = preg_replace(Array('!<!s','!>!s'),Array('&#lt;','&#gt;'),$txt);
//		$txt = preg_replace(Array('!&lt;!s','!&gt;!s'),Array('<','>'),$txt);
//		$txt = preg_replace(Array('!&#lt;!s','!&#gt;!s'),Array('<','>'),$txt);
		$txt = preg_replace('!&amp;!s','&#;',$txt);
		$txt = preg_replace(Array('!&lt;!s','!&gt;!s','!&quot;!s'),Array('<','>','"'),$txt);
		$txt = preg_replace('!&#;!s','&',$txt);
		if(WIKIDEBUG===TRUE)
			echo "<pre>txt_after=---start---&gt;$txt&lt;---end---</pre><br/>\n";
		return(preg_replace('!(<|&lt;)?noinclude(>|&gt;)?.*(<|&lt;)?/noinclude(>|&gt;)?!s','',$txt));
	}
	echo "Fehler beim Wiki export. rc=$rc";
	return(FALSE);
}

/* array=wikiGetToken(object=client,string=page)
 * returns array of edittoken for the requested page or false on error
 * requires wikiLogin on most wiki systems before start */
function wikiGetToken(&$client,$page) {
	$postArray = Array(
		'prop'=>'info',
		'titles'=>$page,
		'intoken'=>'edit',
		'format'=>'php',
		'action'=>'query'
	);
	$rc = $client->post(WIKIAPI,$postArray);
	$res = $client->currentResponse();
	if(WIKIDEBUG===TRUE) {
		echo "<pre>wikiGetToken(client,$page)";
		echo "\npostArray:";
		var_dump($postArray);
		echo "\nrc=$rc";
		echo "\nres:";
		var_dump($res);
		echo "</pre><br/>\n";
	}
	if($rc===200) {
		$result = unserialize($res['body']);
		if(WIKIDEBUG===TRUE) {
			echo "<pre>result=";
			var_dump($result);
			echo "</pre><br/>\n";
		}
		$idx = array_keys($result['query']['pages']);
		if(WIKIDEBUG===TRUE) {
			echo "<pre>idx:";
			var_dump($idx);
			echo "</pre><br/>\n";
		}
		if(isset($result['query']['pages'][$idx[0]]['starttimestamp']) &&
		   isset($result['query']['pages'][$idx[0]]['edittoken'])) {
			return(Array(
			 'starttimestamp'=>$result['query']['pages'][$idx[0]]['starttimestamp'],
			 'edittoken'=>$result['query']['pages'][$idx[0]]['edittoken']
			));
		}
	}
	echo "Fehler beim Anfordern des Edittoken. rc=".$rc;
	if(isset($result) && array_key_exists('error',$result))
	 echo "; error=".$result['error']['info']."(".$result['error']['code'].")";
	return(FALSE);
}

/* boolean=wikiEdit(object=client,array=token,string=page,string=content,array=options)
* returns true on success
* creates, updates or adds wiki page.
* options can contain: recreate=1, createonly=1, nocreate=1, section=(0=top,new=new), prependtext=1, appendtext=1,
*   summary=text, notminor=1, minor=1
* requires editToken and wikiLogin on most wiki systems before start */
function wikiEdit(&$client,$token,$page,$txt,$options = Array()) {
	$postArray = Array(
		'title'=>$page,
		'text'=> $txt,
		'token'=>$token['edittoken'],
		'starttimestamp'=>$token['starttimestamp'],
		'format'=>'php',
		'action'=>'edit'
	)+$options;
	$rc = $client->post(WIKIAPI,$postArray);
	$res = $client->currentResponse();
	if(WIKIDEBUG===TRUE) {
		echo "<pre>wikiEdit(client,token,$page,txt,options)";
		echo "\ntoken:";
		var_dump($token);
		echo "\noptions:";
		var_dump($options);
		echo "\npostArray:";
		echo "\ntxt=$txt";
		echo "\npostArray:";
		var_dump($postArray);
		echo "\nrc=$rc";
		echo "\nres:";
		var_dump($res);
		echo "</pre><br/>\n";
	}
	if($rc===200) {
		return(TRUE);
	}
	echo "Fehler beim Editieren. rc=".$rc;
	if(isset($result) && array_key_exists('error',$result))
	 echo "; error=".$result['error']['info']."(".$result['error']['code'].")";
	return(FALSE);
}

/* boolean=wikiLogout(object=client)
 * returns true on success */
function wikiLogout(&$client) {
	$postArray = Array(
		'action' => 'logout');
	$rc = $client->post(WIKIAPI,$postArray);
	$res = $client->currentResponse();
	if(WIKIDEBUG===TRUE) {
		echo "<pre>wikiLogout(client,$page)";
		echo "\npostArray:";
		var_dump($postArray);
		echo "\nrc=$rc";
		echo "\nres:";
		var_dump($res);
		echo "</pre><br/>\n";
	}
	if($rc===200) {
		return(TRUE);
	}
	return(FALSE);
}

?>

