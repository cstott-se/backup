<?php
  // Turn on error reporting
  ini_set('display_errors',1);
  ini_set('display_startup_errors',1);
  error_reporting(-1);

  $servername = "localhost";
  $username = "xxx";
  $password = "xxx";
  $dbname = "kanboard";

  // IP of VM running Kanboard
  $hostIP = "xxx";

  // Start Script
  echo 'Running subtask script...'.PHP_EOL;

  // Connect to database
  $mysqli = new mysqli($servername,$username,$password,$dbname);
  if ($mysqli->connect_errno) {
    echo "Error: Failed to make a MySQL connection, here is why: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    exit;
  }

  // subtasks.status => 0 not started, 1 in progress, 2 completed
  $sql = 'SELECT users.email AS email, tasks.id AS task_id, tasks.title AS project_name, tasks.project_id AS project_id, tasks.date_due AS date_due, subtasks.title AS subtask_name FROM tasks, subtasks, users WHERE tasks.id = subtasks.task_id AND users.id = subtasks.user_id AND subtasks.status != 2 AND tasks.is_active = 1 ORDER BY users.email, tasks.id';

  if (!$result = $mysqli->query($sql)) {
    echo "Error: Our query failed to execute and here is why: \n";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $mysqli->errno . "\n";
    echo "Error: " . $mysqli->error . "\n";
    exit;
  }

    $first_time = true;
  // Go through result of SQL
  while ($row = $result->fetch_assoc()) {
    if ($first_time) {
    	$email = $row['email'];
    	$project = $row['project_name'];
    	$first_time = false;

        // Set up email
        $message = '<html><body>';
        $message .= '<p>Attached is a list of all your open subtasks:</p>';

        add_header($message,$row['project_name']);
    }
    if (strcmp($email, $row['email']) == 0) {
      if (strcmp($project, $row['project_name']) == 0) {
        add_task($message,$row['task_id'],$row['project_id'],$row['subtask_name'],$row['date_due'],$hostIP);
      }
      else {
      	// End table for previous project
        $message .= '</table><br/><br/>';
    	  $project = $row['project_name'];

        // Create table for new project
        add_header($message,$row['project_name']);
        add_task($message,$row['task_id'],$row['project_id'],$row['subtask_name'],$row['date_due'],$hostIP);
      }
    }
    else {
      // End table for previous project
      $message .= '</table><br/><br/>';

      // Send email
      my_send_mail($email, $message);
      
      // Prepare for next email
      $email = $row['email'];
      $project = $row['project_name'];
 
      // Set up email
      $message = '<html><body>';
      $message .= '<p>Attached is a list of all your open subtasks:</p>';

      add_header($message,$row['project_name']);
      add_task($message,$row['task_id'],$row['project_id'],$row['subtask_name'],$row['date_due'],$hostIP);
    }
  }
  $result->free();
  $mysqli->close();

  // Send last message
  $message .= '</table><br/><br/>';
  my_send_mail($email, $message);

  // Done Script
  echo 'Finished script.'.PHP_EOL;

function  my_send_mail($to,&$message) 
{
  $from = "notifications@kanboard.local";
  $headers = "From:" . $from . "\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
  $subject = "Open Subtask Report";
  $message .= '</body></html>';

  if(mail($to,$subject,$message, $headers)) {
    echo "Mail sent successfully.\n";
  } else {
    echo "Mail could not be sent.\n";
  }
}

function add_header(&$message,$project_name) {
  // Add "header" for current project to email
  $message .= '<h2>Open sub-tasks for the project: ' . $project_name . '</h2>';
  // Build Table of results
  $message .= '<table style="font-size: .8em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 20px;" cellpadding=5 cellspacing=1>';
  $message .= '<tr style="background: #fbfbfb; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">';
  $message .= '<th style="border: 1px solid #eee; width:10%;">Id</th>';
  $message .= '<th style="border: 1px solid #eee; width:50%;">Title</th>';
  $message .= '<th style="border: 1px solid #eee; width:15%;">Due date</th>';
}

function add_task(&$message, $tasks_id, $project_id, $subtask_name, $due_date, $host_IP) {
  $message .= '<tr style="overflow: hidden; background: #fff; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">';
  $message .= '<td style="border: 1px solid #eee; text-align: center;">KB-' . $tasks_id . '</td>';
  $message .= '<td style="border: 1px solid #eee;"><a href="http://' . $host_IP . '/kanboard.local/?controller=task&action=show&task_id='.$tasks_id.'&project_id='.$project_id.'">'.$subtask_name.'</a>';

  $message .= '<td style="border: 1px solid #eee; text-align: center;">';
  if ($due_date > 0) {
    $message .= date("Y-m-d", $due_date);
  }
  $message .= '</td>';
  $message .= '</tr>';
}

?>
