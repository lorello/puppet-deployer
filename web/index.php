<?php
ini_set("log_errors", 1);
ini_set("error_log", "hook.log");   //use this to log errors that are found in the script (change the filename and path to a log file of your choosing), the command will make the file automatically
error_reporting(E_ALL);
ignore_user_abort(true);            //don't want users screwing up the processing of the script by stopping it mid-process

require_once __DIR__.'/../vendor/autoload.php';

$hostnameRegExp = '([a-zA-Z0-9](?:(?:[a-zA-Z0-9-]*|(?<!-)\.(?![-.]))*[a-zA-Z0-9]+)?)';

$app = new Silex\Application();

$app['debug'] = true;


use Symfony\Component\HttpFoundation\Response;

$app->error(function (\Exception $e, $code) {
  if ($app['debug']) {
    return;
  }


    return new Response('We are sorry, but something went terribly wrong.');
});


$app->get('/', function() use ($app) {
  return "Syntax: /deploy/PROJECT_NAME/on/HOSTSLIST\n\nwhere HOSTSLIST is HOST+HOST+HOST\n";
});


# TODO: deploy/on/envname
# env reside on a yaml file

$app->get('/deploy/{projectName}/on/{hostsList}', function($projectName, $hostsList) use ($app) {
  $hosts = explode('+', $hostsList);
  $output = '';
  foreach($hosts as $host) {
    if ($app['debug']) {
      $output .= "Deploying $projectName on $host\n";
    } else {
      $deployCmd = 'cd /root/setup && puppet apply puppet/manifests/default.pp';
      $conn = ssh2_connect($host, 22);
      if (ssh2_auth_pubkey_file(
        $conn, 
        'marvin',
        '/home/marvin/.ssh/id_rsa.pub',
        '/home/marvin/.ssh/id_rsa', 'secret')
      ) {
        echo "Public Key Authentication Successful\n";
      } else {
        $app->abort('Public Key Authentication Failed');
      }
      ssh2_exec($conn, "sudo $deployCmd");
    }
  }
  return $output;
})
->assert('projectName', '[a-z][a-zA-Z0-9]+')
->assert('hostsList', "$hostnameRegExp(\+$hostnameRegExp)*");

$app->run();
