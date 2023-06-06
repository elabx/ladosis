<?php

// Load the Google API PHP Client Library.
// $analytics = initializeAnalytics();
// $response = getReport($analytics);
// //d($response);
// //d($response->getReports());
// //d(count($response->getReports()));
// //d($response[0]->getData()->getRows()[0]->getMetrics());

// foreach($response->getReports() as $i => $report){
//     //d($reports->getData()->getRows());

//     //d($reports);


//     //$report = $reports[0];
//     $header = $report->getColumnHeader();
//     $dimensionHeaders = $header->getDimensions();
//     d($dimensionHeaders);
//     $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
//     $rows = $report->getData()->getRows();

//     $sessionsPerPageData = array();

//     for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
//       $row = $rows[ $rowIndex ];
//       $dimensions = $row->getDimensions();
//       $metrics = $row->getMetrics();
//       for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
//           $sessionsPerPageData[$rowIndex]["path"] = $dimensions[$i];
//           //print_r($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
//       }

//       for ($j = 0; $j < count($metrics); $j++) {
//         $values = $metrics[$j]->getValues();
//         for ($k = 0; $k < count($values); $k++) {
//           $entry = $metricHeaders[$k];
//           $sessionsPerPageData[$rowIndex]["sessions"] = $values[$k];
//           //print_r($entry->getName() . ": " . $values[$k] . "\n");
//         }
//       }
//     }

//     d($sessionsPerPageData);

// }

$mostPopular = $cache->get("mostpopular", 3600);

if(!$mostPopular){
  $latest = $modules->GoogleAnalyticsAPI->getMostpopular();
  d($latest);
  $output = "";
  foreach($latest as $latest){
    $article = $pages->get($latest['path']);
    //d($article->title);
    $output .= wireRenderFile("inc/article-item.php", array("article" => $article));
    
  }
  $cache->save("mostpopular", $output);
  echo $output;
}



/**
 * Initializes an Analytics Reporting API V4 service object.
 *
 * @return An authorized Analytics Reporting API V4 service object.
 */
function initializeAnalytics()
{

  // Use the developers console and download your service account
  // credentials in JSON format. Place them in this directory or
  // change the key file location if necessary.
  $KEY_FILE_LOCATION = wire("config")->paths->assets . '/service.json';

  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("Hello Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_AnalyticsReporting($client);

  //echo print_r($analytics, true);
  
  return $analytics;
}


/**
 * Queries the Analytics Reporting API V4.
 *
 * @param service An authorized Analytics Reporting API V4 service object.
 * @return The Analytics Reporting API V4 response.
 */
function getReport($analytics) {

  // Replace with your view ID, for example XXXX.
  $VIEW_ID = "132928110";

  // Get unique pageviews and average time on page.
  // Create the DateRange object.
  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
  $dateRange->setStartDate("7daysAgo");
  $dateRange->setEndDate("today");

  // Create the Metrics object.
  $sessions = new Google_Service_AnalyticsReporting_Metric();
  $sessions->setExpression("ga:sessions");
  $sessions->setAlias("sessions");

  $dimensions = new Google_Service_AnalyticsReporting_Dimension();
  //$dimensions->setExpression("ga:sessions");
  $dimensions->setName("ga:pagePath");

  $orderby = new Google_Service_AnalyticsReporting_OrderBy();
  //$orderby->setOrderType("HISTOGRAM_BUCKET");
  $orderby->setFieldName("ga:sessions");
  $orderby->setSortOrder("DESCENDING");
  
  // Create the ReportRequest object.
  $request = new Google_Service_AnalyticsReporting_ReportRequest();
  $request->setViewId($VIEW_ID);
  $request->setDateRanges($dateRange);
  $request->setMetrics(array($sessions));
  $request->setDimensions(array($dimensions));
  $request->setOrderBys(array($orderby));


  
  

  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests( array( $request) );
  return $analytics->reports->batchGet( $body );
  //return get_class($analytics->reports->batchGet( $body ));
}


/**
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param An Analytics Reporting API V4 response.
 */
function printResults($reports) {
  for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
    $report = $reports[ $reportIndex ];
    $header = $report->getColumnHeader();
    $dimensionHeaders = $header->getDimensions();
    $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
    $rows = $report->getData()->getRows();

    
    for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
      $row = $rows[ $rowIndex ];
      $dimensions = $row->getDimensions();
      $metrics = $row->getMetrics();
      for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
        print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
      }

      for ($j = 0; $j < count($metrics); $j++) {
        $values = $metrics[$j]->getValues();
        for ($k = 0; $k < count($values); $k++) {
          $entry = $metricHeaders[$k];
          print($entry->getName() . ": " . $values[$k] . "\n");
        }
      }
    }
  }
}
