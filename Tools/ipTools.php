<?php
namespace phpWTL\Tools;

/**
  * Toolbox for IP address related functions
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api
  */
class ipTools {
	/** Default number of rightmost segments in ip4 address to anonymize */
	const IP4_ANON_CUT= 1;
	/** Default number of rightmost segments in ip6 address to anonymize */
	const IP6_ANON_CUT= 5;
	/** Default wildcard character for substitution of anonymized parts in ip4 address */
	const IP4_ANON_WC= '0';
	/** Default wildcard character for substitution of anonymized parts in ip6 address */
	const IP6_ANON_WC= '';

	/**
	  * Checks if given string is syntactically an ip4 address
	  *
	  * @param string $ip The IP address to check.
	  * @return boolean true if address is ip4. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function isIP4($ip) {
		return (preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $ip)==1 ? true : false);
	}

	/**
	  * Checks if given string is syntactically an ip6 address
	  *
	  * @param string $ip The IP address to check.
	  * @return boolean true if address is ip6. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function isIP6($ip) {
		return (preg_match("/(?:[a-f0-9]{0,4}:){7}[a-f0-9]{0,4}/", $ip)==1 ? true : false);
	}

	/**
	  * Anonymize a given IP address (ip4 or ip6)
	  *
	  * @param string $ip The IP address to be anonymized.
	  * @param array $params Parameter: ip4cut / ip6cut = Number of rightmost segments in address to anonymize, ip4wildcard / ip6wildcard = character for substitution of anonymized parts
	  * @return string anonymized IP. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
    public static function ipAnon($ip, $params= null) {
	$ret= null;

	$ip4= ipTools::isIP4($ip);
	$ip6= ipTools::isIP6($ip);
	if ($ip4 || $ip6) {
		if (!$params) {
			$params= array(
				'ip4cut' => ipTools::IP4_ANON_CUT, 
				'ip6cut' => ipTools::IP6_ANON_CUT, 
				'ip4wildcard' => ipTools::IP4_ANON_WC, 
				'ip6wildcard' => ipTools::IP6_ANON_WC
			);
		}
		if (array_key_exists("ip4cut", $params)) {
			if ($params["ip4cut"]<1 || $params["ip4cut"]>4) $params["ip4cut"]= ipTools::IP4_ANON_CUT;
		} else {
			$params["ip4cut"]= ipTools::IP4_ANON_CUT;
		}
		if (array_key_exists("ip6cut", $params)) {
			if ($params["ip6cut"]<1 || $params["ip6cut"]>8) $params["ip6cut"]= ipTools::IP6_ANON_CUT;
		} else {
			$params["ip6cut"]= ipTools::IP6_ANON_CUT;
		}
		if (!array_key_exists("ip4wildcard", $params)) {
			$params["ip4wildcard"]= ipTools::IP4_ANON_WC;
		}
		if (!array_key_exists("ip6wildcard", $params)) {
			$params["ip6wildcard"]= ipTools::IP6_ANON_WC;
		}
		$deli= ($ip4 ? "." : ":");
		$cut= ($ip4 ? 4-$params["ip4cut"] : 8-$params["ip6cut"]);
		$wc= ($ip4 ? $params["ip4wildcard"] : $params["ip6wildcard"]);
		$segments= explode($deli, $ip);
		$ret= "";
		for($i=0;$i<count($segments);$i++) {
			if ($i<$cut) {
				$ret= $ret.$segments[$i];
			} else {
				$ret= $ret.$wc;
			}
			if ($i<count($segments)-1) $ret= $ret.$deli;
		}
	}	

	return $ret;
}

}
?>