<?php
require('/home/lartik/pro/php/composer-global-dep/vendor/digitalnature/php-ref/ref.php');
require "vendor/autoload.php";

use pswg\PSwg;
use pswg\PSwgParser;

$argvs = $_SERVER['argv'];
$pswg = new PSwg();

$parser = new PSwgParser();
$parser->saveFile = $argvs[2];
$parser->inputFile = $argvs[1];

$pswg->saveFromParser($parser);