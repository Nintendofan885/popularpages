<?php
/**
 * This is a script for manually regenerating the popular pages report for a
 * single WikiProject. It takes two command line arguments: the wiki
 * in the form of lang.project, and the WikiProject (as recorded by the
 * PageAssessments extension).
 *
 * Example usage:
 *   php generateReport.php en.wikipedia "Alternative education"
 */

// Exit if not run from command-line
if ( PHP_SAPI !== 'cli' ) {
	echo "This script should be run from the command-line.\n";
	die();
}

if ( !isset( $argv[1] ) || preg_match( '/^\w+\.\w+$/', $argv[1] ) !== 1 ) {
	echo "Please specify wiki in the format lang.project (such as en.wikipedia)\n";
	die();
}

if ( !isset( $argv[2] ) ) {
	echo "Please specify the name of the project as a command line argument.\n";
	die();
}

date_default_timezone_set( 'UTC' );
include_once 'vendor/autoload.php';

$api = new ApiHelper( $argv[1] );

wfLogToFile( 'Running script to generate report for project ' . $argv[2] . ' on ' . $argv[1] );

$projectConfig = $api->getProject( $argv[2] );

if ( null === $projectConfig ) {
	echo "Project configuration not found.\n";
} else {
	// Instantiate a new ReportUpdater with the specified project
	$updater = new ReportUpdater( $argv[1] );
	$updater->updateReports( $projectConfig );
}
