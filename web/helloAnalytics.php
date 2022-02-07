<?php
date_default_timezone_set('Asia/Bangkok');


// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';
$start_date = '2020-12-01';
$end_date = '2021-12-31';
$max_results = '25';


$analytics = initializeAnalytics();
$profile = getFirstProfileId($analytics);
$results = getResults($analytics, $profile, $start_date, $end_date, $max_results);
$total_pageview = getTotalPageview($analytics, $profile, $start_date, $end_date, $max_results);

function initializeAnalytics()
{
  // Creates and returns the Analytics Reporting service object.

  // Use the developers console and download your service account
  // credentials in JSON format. Place them in this directory or
  // change the key file location if necessary.
  $KEY_FILE_LOCATION = __DIR__ . '/ga-api-report.json';

  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("Hello Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_Analytics($client);

  return $analytics;
}

function getFirstProfileId($analytics)
{
  // Get the user's first view (profile) ID.

  // Get the list of accounts for the authorized user.
  $accounts = $analytics->management_accounts->listManagementAccounts();

  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
    $firstAccountId = $items[0]->getId();

    // Get the list of properties for the authorized user.
    $properties = $analytics->management_webproperties
      ->listManagementWebproperties($firstAccountId);

    if (count($properties->getItems()) > 0) {
      $items = $properties->getItems();
      $firstPropertyId = $items[0]->getId();

      // Get the list of views (profiles) for the authorized user.
      $profiles = $analytics->management_profiles
        ->listManagementProfiles($firstAccountId, $firstPropertyId);

      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();

        // Return the first view (profile) ID.
        return $items[0]->getId();
      } else {
        throw new Exception('No views (profiles) found for this user.');
      }
    } else {
      throw new Exception('No properties found for this user.');
    }
  } else {
    throw new Exception('No accounts found for this user.');
  }
}

function getResults($analytics, $profileId, $start_date, $end_date, $max_results)
{
  // Calls the Core Reporting API and queries for the number of sessions
  // for the last seven days.
  return $analytics->data_ga->get(
    'ga:' . $profileId,
    $start_date,
    $end_date,
    'ga:sessions',
    [
      'dimensions' => 'ga:pageTitle, ga:pagepath, ga:date',
      'metrics' => 'ga:pageviews',
      'sort' => '-ga:pageviews',
      'max-results' => $max_results
    ]

  );
}


function getTotalPageview($analytics, $profileId, $start_date, $end_date, $max_results)
{
  // Calls the Core Reporting API and queries for the number of sessions
  // for the last seven days.
  return $analytics->data_ga->get(
    'ga:' . $profileId,
    $start_date,
    $end_date,
    'ga:sessions',
    [
      'metrics' => 'ga:pageviews',
      'max-results' => $max_results
    ]

  );
}


function printResults($results)
{
  // Parses the response from the Core Reporting API and prints
  // the profile name and total sessions.
  if (count($results->getRows()) > 0) {

    // Get the profile name.
    $profileName = $results->getProfileInfo()->getProfileName();

    // Get the entry for the first entry in the first row.
    $rows = $results->getRows();
    echo '<pre>';
    print_r($rows);
    // $sessions = $rows[0][0];

    // Print the results.

    print "First view (profile) found: $profileName\n";
    // print "Total sessions: $sessions\n";
  } else {
    print "No results found.\n";
  }
}

function printDataTable(&$results)
{
  $table = '';
  $total_pageview = 0;
  if (count($results->getRows()) > 0) {
    $table .= '';

    // Print headers.

    // Print table rows.
    foreach ($results->getRows() as $key => $row) {

      $total_pageview += $row[3];
      $date = date('d-m-Y', strtotime($row[2]));
      $today = date('d-m-Y');
      $trClass = '';
      if ($date === $today) {
        $trClass = 'hilight';
      }
      $table .= '<tr class="' . $trClass . '">';
      $table .= '<td>' . $key + 1 . '</td>';
      foreach ($row as $key => $cell) {
        $tdClass = '';
        if ($key == 2) {
          $tdClass .= 'text-center';
        }
        if ($key == 3) {
          $tdClass .= 'text-right text-bold';
        }
        $table .= '<td class="' . $tdClass . '">'
          . htmlspecialchars(($key !== 2) ? $cell : DateThai($cell), ENT_NOQUOTES)
          . '</td>';
      }
      $table .= '</tr>';
    }
    $table .= '';
  } else {
    $table .= '<p>No Results Found.</p>';
  }
  return ['table' => $table, 'total_pageview' => $total_pageview];
}

function DateThai($strDate, $show_time = false)
{
  $strYear = date("Y", strtotime($strDate)) + 543;
  $strMonth = date("n", strtotime($strDate));
  $strDay = date("j", strtotime($strDate));
  $strHour = date("H", strtotime($strDate));
  $strMinute = date("i", strtotime($strDate));
  $strSeconds = date("s", strtotime($strDate));
  $strMonthCut = array("", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค.");
  $strMonthThai = $strMonthCut[$strMonth];
  $result = "$strDay $strMonthThai $strYear";
  if ($show_time) {
    $result .= " เวลา $strHour:$strMinute";
  }
  return $result;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<style>
  .text-center {
    text-align: center;
  }

  .text-right {
    text-align: right;
  }

  .text-bold {
    font-weight: bold;
    ;
  }

  .s-container {
    margin: 0 auto;
    width: 1140px;
    padding: 0 15px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  table th {
    background-color: blue;
    color: #ffffff;
  }

  table,
  th,
  td {
    border: 1px solid #c4c4c4;
  }

  .total-pageview {
    background: yellow;
    text-decoration: underline;
    font-weight: bold;

  }

  .hilight {
    background: #b4f8ff;
  }
</style>

<body>
  <div class="s-container">
    <div class="text-center">
      <h1>Top 25 ข่าวที่มีเพจวิวสูงสุด <?php echo DateThai(date('Y-m-d H:i:s'), true); ?></h1>
    </div>
    <?php // printDataTable($results); 
    ?>
    <table>
      <tbody>
        <tr>
          <th>No.</th>
          <th>Title</th>
          <th>Link</th>
          <th>วันที่ผลิตข่าว</th>
          <th>Pageview</th>
        </tr>

        <?php
        $data = printDataTable($results);
        echo $data['table'];
        /*
        $totalPageview = 0;
        foreach ($mocks as $key => $data) :
          $totalPageview += $data['pageview'];
        ?>
          <tr class="hilight">
            <td class="text-center"><?php echo $key + 1; ?></td>
            <td><?php echo $data['title']; ?></td>
            <td class="text-right text-bold"><?php echo $data['pageview']; ?></td>
            <td class="text-center"><?php echo DateThai($data['date']); ?></td>
          </tr>
        <?php endforeach; 
        */ ?>
        <tr>
          <td></td>
          <td class="text-center">เพจวิวรวม</td>
          <td></td>
          <td></td>
          <td class="total-pageview text-right"><?php echo $total_pageview['rows'][0][0]; ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</body>

</html>