<?php

class ReportUpdater {

	protected $api;

	/**
	 * ReportUpdater constructor.
	 * If a projects array is provided, the constructor fetches the config info for those projects and updates them
	 *
	 * @param null|array $projects List of projects to update
	 */
	public function __construct() {
		$this->api = new ApiHelper();
	}

	/**
	 * Update popular pages reports. Primary execution point.
	 *
	 * @param array $config The JSON config from the wiki page
	 */
	public function updateReports( $config ) {
		// Make sure config isn't empty
		if ( !is_array( $config ) || count( $config ) < 1 ) {
			logToFile( 'Error: Invalid config. Aborting!' );
			return;
		}
		foreach ( $config as $project => $info ) {
			// Check that config values are set
			if ( !isset( $info['Name'] ) || !isset( $info['Limit'] ) || !isset( $info['Report'] ) ) {
				logToFile( 'Error: Incomplete data in config for ' . $project . '. Skipping.' );
				continue;
			}
			logToFile( 'Beginning to process: ' . $info['Name'] );
			// Check the project exists
			if ( !$this->api->doesTitleExist( $project ) ) {
				logToFile( 'Error: Project page for ' . $info['Name'] . ' does not exist! Skipping.' );
				continue;
			}
			$pages = $this->api->getProjectPages( $info['Name'] ); // Returns { 'title' => ['class'=>'', 'importance'=>''],...}
			if ( empty( $pages ) ) {
				continue;
			}
			// Get dates for last month
			$start = strtotime( 'first day of previous month' );
			$end = strtotime( 'last day of previous month' );
			if ( count( $pages ) > 1000000 ) { // See T164178
				logToFile( 'Error: ' . $project . ' is too large. Skipping.' );
				continue;
			}
			$views = $this->api->getMonthlyPageviews( array_keys( $pages ), date( 'Ymd00', $start ), date( 'Ymd00', $end ) );
			// Compute total views for the month
			$totalViews = array_sum( array_values( $views ) );
			$views = array_slice( $views, 0, $info['Limit'], true );
			$hasListSection = $this->api->doesListSectionExist( $info['Report'] );
			$output = '';
			// If the report page doesn't already exist, include a default header:
			if ( !$hasListSection ) {
				$output .= '
This is a list of pages in the scope of [[' . $project . ']] along with pageviews.

To report bugs, please write on the [[meta:User_talk:Community_Tech_bot|Community tech bot]] talk page on Meta.

';
			}
			$output .=
"== List ==\n" .
"<!-- Changes made below this line will be overwritten on the next update. -->\n" .
'Period: ' . date( 'Y-m-d', $start ) . ' to ' . date( 'Y-m-d', $end ) . '.
Total views: {{formatnum:' . $totalViews . '}}

Updated on: ~~~~~

{| class="wikitable sortable" style="text-align: left;"
! Rank
! Page title
! Views
! Views per day (average)
! Assessment
! Importance
! Link to pageviews tool
|-
';
			$index = 1;
			foreach ( $views as $title => $view ) {
				$output .= '| ' . $index . '
| [[' . $title . ']]
| {{formatnum:' . $view . '}}
| {{formatnum:' . floor( $view / ( floor( ( $end - $start ) / ( 60 * 60 * 24 ) ) ) ) . '}}
{{class|' . $pages[$title]['class'] . '}}
{{importance|' . $pages[$title]['importance'] . '}}
| [https://tools.wmflabs.org/pageviews/?project=en.wikipedia.org&start=' . date( 'Y-m-d', $start ) . '&end=' . date( 'Y-m-d', $end ) . '&pages=' .addslashes( str_replace( ' ', '_', $title ) ) . ' Link]
|-
';
				$index++;
			}
			$output .= '
|}
[[Category:Lists of popular pages by WikiProject]]';
			// Update report text on wiki page
			if ( $hasListSection ) {
				// Update only the section if it exists
				$this->api->setText( $info['Report'], $output, 1 );
			} else {
				// Update complete page
				$this->api->setText( $info['Report'], $output );
			}
			logToFile( 'Finished processing: ' . $info['Name'] );
		}
		// Update index page
		$this->api->updateIndex( 'User:Community Tech bot/Popular pages' );
	}
}
