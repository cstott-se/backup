<?php

$csv = array_map('str_getcsv', file('data.csv'));

  array_walk($csv, function(&$a) use ($csv) {
    $a = array_combine($csv[0], $a);
  });
  array_shift($csv); # remove column header

print_r ($csv);

?>
