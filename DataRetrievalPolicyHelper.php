<?php
namespace phpWTL;

/**
  * Helper class for data retrieval policies
  *
  * @author Michael Beyer <mgbeyer@gmx.de>
  * @version v0.1.0
  * @api
  */
class DataRetrievalPolicyHelper {

	/**
	  * Check the existence of a specific retrieval policy.
	  *
	  * @param array $retrievalPolicies Array of DataRetrievalPolicy objects. 
	  * @param string $policy_name 
	  * @return boolean 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function existsDataRetrievalPolicy($retrievalPolicies, $policy_name) {
		$ret= false;
		foreach ($retrievalPolicies as $k=>$f) {
			if ($f->name==$policy_name) $ret= true;
		}
		return $ret;
	}
	
	/**
	  * Get the names of all retrieval policies.
	  *
	  * @param array $retrievalPolicies Array of DataRetrievalPolicy objects. 
	  * @return array 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function getDataRetrievalPolicyNames($retrievalPolicies) {
		$ret= array();
		foreach ($retrievalPolicies as $k=>$f) {
			array_push($ret, $f->name);
		}
		return $ret;
	}
	/**
	  * Get the DataRetrievalPolicy object of a specific retrieval policy.
	  *
	  * @param array $retrievalPolicies Array of DataRetrievalPolicy objects. 
	  * @param string $policy_name 
	  * @return object 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function getDataRetrievalPolicyDescription($retrievalPolicies, $policy_name) {
		foreach ($retrievalPolicies as $k=>$f) {
			if ($f->name==$policy_name) return $f;
		}
	}
	/**
	  * Get the attribute names of an DataRetrievalPolicy object (based on the given array).
	  *
	  * @param array $retrievalPolicies Array of DataRetrievalPolicy objects. 
	  * @return array 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function getDataRetrievalPolicyAttributeNames($retrievalPolicies) {
		return array_keys(get_object_vars($retrievalPolicies[0]));
	}
	/**
	  * @param array $retrievalPolicies Array of DataRetrievalPolicy objects. 
	  * @param string $policy_name Name of the desired policy. 
	  * @param string $attribute_name Name of the DataRetrievalPolicy attribute. 
	  * @return string Value of the DataRetrievalPolicy attribute for the desired policy name. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function getDataRetrievalPolicyAttribute($retrievalPolicies, $policy_name, $attribute_name) {				
		foreach ($retrievalPolicies as $k=>$f) {
			if ($f->name==$policy_name && property_exists($f, $attribute_name)) return $f->{$attribute_name};
		}
	}
	/**
	  * @param array $retrievalPolicies Array of DataRetrievalPolicy objects. 
	  * @param string $policy_name Name of the desired policy. 
	  * @param string $attribute_name Name of the DataRetrievalPolicy attribute ("name", because it serves as an ID, is immutable). 
	  * @param string $value Value to assign the DataRetrievalPolicy attribute for the desired policy name. 
	  *
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function setDataRetrievalPolicyAttribute($retrievalPolicies, $policy_name, $attribute_name, $value) {
		if ($attribute_name!="name" && in_array($attribute_name, static::getDataRetrievalPolicyAttributeNames($retrievalPolicies))) {
			foreach ($retrievalPolicies as $k=>$f) {
				if ($f->name==$policy_name) {
					$f->{$attribute_name}= $value;
				}
			}
		}
	}

	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function getDataRetrievalPolicyFlag($retrievalPolicies, $policy_name) {		
		return static::getDataRetrievalPolicyAttribute($retrievalPolicies, $policy_name, "flag");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function getDataRetrievalPolicyParameter($retrievalPolicies, $policy_name) {
		return static::getDataRetrievalPolicyAttribute($retrievalPolicies, $policy_name, "parameter");
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function setDataRetrievalPolicyFlag($retrievalPolicies, $policy_name, $value) {
		static::setDataRetrievalPolicyAttribute($retrievalPolicies, $policy_name, "flag", $value);
	}
	/**
	  * @author Michael Beyer <mgbeyer@gmx.de>
	  * @version v0.1.0
	  * @api
	  */
	public static function setDataRetrievalPolicyParameter($retrievalPolicies, $policy_name, $value) {
		static::setDataRetrievalPolicyAttribute($retrievalPolicies, $policy_name, "parameter", $value);
	}

}
?>