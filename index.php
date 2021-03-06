<?php

require_once __DIR__.'/vendor/silex.phar';

$app = new Silex\Application();

// Add option for setting debug mode from environment
if (isset($_SERVER['debug'])) {
  $app['debug'] = $_SERVER['debug'];
}

$app->before(function() use ($app) {
  // The URL for the Jenkins instance to use
  $app->jenkinsUrl = "http://jenkins.reload.dk";
});

/**
 * Generate a text status widget for a single job.
 */
$app->get('/job/{job}/status', function ($job) use ($app) {
  $doc = new DOMDocument('1.0', 'UTF-8');
  $root = $doc->appendChild($doc->createElement('root'));

  // Fetch the data from Jenkins
  $data = json_decode(file_get_contents($app->jenkinsUrl . '/job/'. $job .'/api/json'));
  if ($data) {
    // Build the text item
    $item = $root->appendChild($doc->createElement('item'));

    $text = $doc->createElement('text');

    $iconUrl = $app->jenkinsUrl . '/static/' . mt_rand() . '/images/48x48/' . $data->healthReport[0]->iconUrl;

    // Assemble a status containing build name, stability and health icon
    $status = '<h1>'.$data->displayName.'</h1>' .
              '<p class="t-size-x14">'. $data->healthReport[0]->description . '</p>'.
              '<img src="'. $iconUrl . '"/>';

    $text->appendChild($doc->createCDATASection($status));

    $item->appendChild($text);

    //If the latest build failded then indicate this using the type
    $type = ($data->lastFailedBuild->number == $data->lastBuild->number) ? 2 : 0;
    $item->appendChild($doc->createElement('type', $type));
  }

  return $doc->saveXML();
});

/**
 * Frontpage route.
 */
$app->get('/', function () use ($app) {
  return '<h1>Geckoboard Jenkins frontpage</h1>';
});

$app->run();