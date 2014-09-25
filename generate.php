#!/usr/bin/php
<?php
/**
 * Simple Daemon READ API
 * Execute on the command line to generate network data.
 * Data produced is stored in a cached JSON file, typically index.json in the /web root.
 * 
 * @author Mark Pfennig
 * @license http://unlicense.org/ 
 */
define('DAEMON_PATH', '/opt/bitmarkd');
define('API_CACHE',  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR );
define('API_CACHE_FILE', API_CACHE . 'index.json');
define('NETWORKHASH_CACHE_FILE', API_CACHE . 'networkhashps.json');

$data = (object)array(
  'generated' => 0,
  'currency' => (object)array(
	'max' => 27579894.73108,
    'current' => 0,
    'symbol' => 'BTM',
    'name' => 'Bitmark'
  ),
  'block' => null,
  'network' => null,
);

$data->generated = askDaemon('getblockcount');

$data->block = $last = getBlockByHeight($data->generated);
$changelast = floor($data->generated/720)*720;
$changenext = $changelast+720;

$data->currency->current = $data->generated*20;

$data->network = (object)array(
  'blocks' => $data->generated,
  'changelast' => $changelast,
  'changenext' => $changenext,
  'difficulty' => $last->difficulty,
  'target' => floor($last->difficulty*35791394.1333),
  'hashrates' => (object)array(
    'b120' => askDaemon('getnetworkhashps 120'),
    'b60' => askDaemon('getnetworkhashps 60'),
    'b30' => askDaemon('getnetworkhashps 30'),
    'b15' => askDaemon('getnetworkhashps 15')
  )
);

file_put_contents( API_CACHE_FILE, json_encode( $data, JSON_PRETTY_PRINT ) );
file_put_contents( NETWORKHASH_CACHE_FILE, json_encode( $data->network->hashrates->b120 ) );

function askDaemon($command) {
  $ask = DAEMON_PATH . ' ' . $command;
  $response = trim(shell_exec($ask), PHP_EOL);
  $data = json_decode($response);
  if(!$data) $data = $response;
  return $data;
}

function getBlockByHeight($height) {
  $hash = askDaemon('getblockhash '. (string)$height);
  return askDaemon('getblock ' . (string)$hash);
}
