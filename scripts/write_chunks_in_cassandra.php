<?php

date_default_timezone_set('UTC');

$app=$argv[1];
$tv=$argv[2];
$bitrate=$argv[3];
$playlist_path=$argv[4];
$chunk_filename=$argv[5];
$chunk_duration=$argv[6];

$last_chunk_num='';

$cluster   = Cassandra::cluster()->withContactPoints('127.0.0.1')->withCredentials("php", "12345")->build();
$keyspace  = 'dvr';
$session   = $cluster->connect($keyspace);

$fileContent = file_get_contents("$playlist_path/$chunk_filename");
$blob = new \Cassandra\Blob($fileContent);
$chunk_content = $blob->bytes();

$table_chunk_content=$app.'_'.$tv.'_'.$bitrate.'_chunk_content';
$table_chunk_info=$app.'_'.$tv.'_'.$bitrate.'_chunk_info';
$table_variant_info=$app.'_variant_info';

$options     = new Cassandra\ExecutionOptions(array('consistency' => Cassandra::CONSISTENCY_LOCAL_ONE));

$qry="SELECT last_chunk_num FROM $table_variant_info WHERE app='$app' AND tv='$tv' AND bitrate=$bitrate";
$statement   = new Cassandra\SimpleStatement($qry);
$result = $session->execute($statement, $options);

foreach ($result as $row) {
  $last_chunk_num=$row['last_chunk_num'];
}

if ($last_chunk_num) {

  $chunk_filename_universal='media_b'.$bitrate.'_'.$last_chunk_num.'.ts';

  $batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);
  $batch->add("INSERT INTO $table_chunk_content (chunk_name, chunk_content) VALUES ('$chunk_filename_universal', $chunk_content)");
  $batch->add("INSERT INTO $table_chunk_info (fake, time_id, chunk_name, chunk_duration) VALUES (1, now(), '$chunk_filename_universal', $chunk_duration)");
  $last_chunk_num++;
  $batch->add("UPDATE $table_variant_info SET last_chunk_num=$last_chunk_num WHERE app='$app' AND tv='$tv' AND bitrate=$bitrate");
  $bitrateult = $session->execute($batch, $options);

}
else {
  die('ERROR: Cannot get last chunk number from Cassandra database!');
}

?>
