<?php
  // Turn on error reporting
  ini_set('display_errors',1);
  ini_set('display_startup_errors',1);
  error_reporting(-1);

  $servername = "localhost";
  $username = "xxx";
  $password = "xxx";
  $dbname = "kanboard";

  $contactlist = 'list.txt';
  $pattern = '/[,\s]/';

  // Hardcoded to work with Infrastucture Team Tasks board
  $projectToken = "xxx";

  // IP of VM running Kanboard
  $hostIP = "xxx";

  // Start Script
  echo 'Running script...'.PHP_EOL;

  // Load contacts in to an Array
  $myArray = array();
  $lines = file($contactlist);
  foreach ($lines as $line_num => $line) {
    $strings = preg_split($pattern, $line);  
    $myArray[$line_num][0] = $strings[0];
    $myArray[$line_num][1] = $strings[0] . "." . $strings[1] . "@xxx.com";
    $myArray[$line_num][2] = $strings[3];
    $myArray[$line_num][3] = substr($line,strlen($strings[0] . $strings[1] . $strings[2] . $strings[3]) + 5);
  }

  // Connect to database
  $mysqli = new mysqli($servername,$username,$password,$dbname);
  if ($mysqli->connect_errno) {
    echo "Error: Failed to make a MySQL connection, here is why: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    exit;
  }

  // Grab first email address in the myArray
  $currentEmail = $myArray[0][1];

  $message = '<html><body>';
  $message .= '<p>' . $myArray[0][0] . ',<br/><br/>Attached is a list of all your open RPI tasks.</p>';
  reset($myArray);

  $rows_present = false;
  // Loop through myArray and create emails based on "status:open $myarray[2]" search
  foreach ($myArray as $project) {
    if (strcmp($project[1], $currentEmail) !== 0) {
      // Send email as recipient changed
      if ($rows_present) {
        my_send_mail($currentEmail,$message);
      }
      // Set up next email
      $rows_present = false;
      $currentEmail = $project[1];
      $message = '<html><body>';
      $message .= '<p>' . $project[3] . ',<br/><br/>Attached is a list of all your open RPI tasks.</p>';
    }

    // Grab next project for current email
    $sql = 'SELECT tasks.id, tasks.title, tasks.date_due, name FROM tasks INNER JOIN users ON tasks.owner_id = users.id WHERE tasks.project_id = 2 AND tasks.is_active = 1 AND tasks.title LIKE "%' . $project[2] . '%"';
    if (!$result = $mysqli->query($sql)) {
      echo "Error: Our query failed to execute and here is why: \n";
      echo "Query: " . $sql . "\n";
      echo "Errno: " . $mysqli->errno . "\n";
      echo "Error: " . $mysqli->error . "\n";
      exit;
    }

    // make sure part at least one row to return
    if ($result->num_rows > 0) {
      $rows_present = true;
      // Add "header" for current project to email
      $message .= '<h2>Open tasks for the project: ' . $project[3] . '</h2>';

      // Build Table of results
      $message .= '<table style="font-size: .8em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 20px;" cellpadding=5 cellspacing=1>';
      $message .= '<tr style="background: #fbfbfb; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">';
      $message .= '<th style="border: 1px solid #eee; width:10%;">Id</th>';
      $message .= '<th style="border: 1px solid #eee; width:50%;">Title</th>';
      $message .= '<th style="border: 1px solid #eee; width:15%;">Due date</th>';
      $message .= '<th style="border: 1px solid #eee; width:25%;">Assignee</th></tr>';

      // Loop through results and add rows to message
      while ($row = $result->fetch_assoc()) {
        $message .= '<tr style="overflow: hidden; background: #fff; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">';
        $message .= '<td style="border: 1px solid #eee; text-align: center;">KB-' . $row['id'] . '</td>';
        $message .= '<td style="border: 1px solid #eee;"><a href="http://' . $hostIP . '/kanboard.local/?controller=task&action=readonly&task_id='.$row['id'].'&token='.$projectToken.'">'.$row['title'].'</a>';
        $message .= '<td style="border: 1px solid #eee; text-align: center;">';
          if ($row['date_due'] > 0) {
            $message .= date("Y-m-d", $row['date_due']);
          }
          $message .= '</td>';
        $message .= '<td style="border: 1px solid #eee;text-align: center;">' . $row['name'] . '</td>';
        $message .= '</tr>';
      }
      $message .= '</table><br/><br/>';
    }
    $result->free();
  }
  $mysqli->close();

  // Lazy hack to address my lack of PHP skills
  if ($rows_present) {
        my_send_mail($currentEmail,$message);
  }

  // Done Script
  echo 'Finished script.'.PHP_EOL;

function  my_send_mail($to,&$message) 
{
  $from = "notifications@kanboard.local";
  $headers = "From:" . $from . "\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
  $subject = "Weekly RPI Open Tasks Report";
  $message .= '<p>If your have any questions regarding these tasks or notice that there are some missing please follow up with your RPI prime for that project.';
  $message .= '</body></html>';
  if(mail($to,$subject,$message, $headers)) {
    echo "Mail sent successfully.\n";
  } else {
    echo "Mail could not be sent.\n";
  }
}
?>
