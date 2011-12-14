<?php
//
// Description
// ===========
// This function will check the user has access to the calendar module, and 
// return a list of other modules enabled for the business.
//
// Arguments
// =========
// business_id: 		The ID of the business the request is for.
// 
// Returns
// =======
//
function ciniki_calendars_checkAccess($ciniki, $business_id, $method) {

	//
	// Check if the module is turned on for the business
	// Check the business is active
	// Get the ruleset for this module
	//
	$strsql = "SELECT ruleset "
		. ", CONCAT_WS('.', ciniki_business_modules.package, ciniki_business_modules.module) AS module_id "
		. "FROM ciniki_businesses, ciniki_business_modules "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_businesses.status = 1 "														// Business is active
		. "AND ciniki_businesses.id = ciniki_business_modules.business_id "
		. "AND ciniki_business_modules.status = 1 "														// Business is active
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'businesses', 'modules', 'module_id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['modules']) || !isset($rc['modules']['ciniki.calendars']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'495', 'msg'=>'Access denied.'));
	}
	$modules = $rc['modules'];

	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok', 'modules'=>$modules);
	}

	//
	// Users who are an owner or employee of a business can see the business alerts
	//
	$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND (groups&0x03) > 0 "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	//
	// Double check business_id and user_id match, for single row returned.
	//
	if( isset($rc['user']) && isset($rc['user']['business_id']) 
		&& $rc['user']['business_id'] == $business_id 
		&& $rc['user']['user_id'] = $ciniki['session']['user']['id'] ) {
		// Access Granted!
		return array('stat'=>'ok');
	}

	//
	// By default, fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'496', 'msg'=>'Access denied.'));
}
?>