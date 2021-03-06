#!/usr/bin/env php
<?php
require dirname(__FILE__) . "/vendor/autoload.php";

use pswg\PSwg;
use pswg\PSwgParser;

$argvs = $_SERVER['argv'];
$pswg = new PSwg();

$parser = new PSwgParser();
$parser->saveFile = $argvs[2];
$parser->inputFile = $argvs[1];
$parser->enumsFile = isset($argvs[3]) ? $argvs[3] : '';
$parser->mode = isset($argvs[4]) ? $argvs[4] : '';

$pswg->saveFromParser($parser);
