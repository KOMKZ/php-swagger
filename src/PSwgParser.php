<?php
namespace pswg;


/**
 *
 */
class PSwgParser
{
	public $saveFile = '';
	public $inputFile = '';
	public function parse(){
		$handle = @fopen($this->inputFile, "r");
		if ($handle) {
			$lineNum = 1;
			$docs = [];
		    while (($buffer = fgets($handle, 4096)) !== false) {
		      	if(preg_match("/\s*\/\*\*\s*\n/", $buffer)){
					$docBlock[] = [$buffer, $lineNum];
					continue;
				}
				if(preg_match("/\s*\*\/\s*\n$docBegin/", $buffer)){
					$docBlock[] = [$buffer, $lineNum];
					$docs[] = implode('', $docBlock);
					$docBlock = [];
				}
				$lineNum++;
		    }
			print_r($docs);
		    if (!feof($handle)) {
		       	throw new \Exception("Error: unexpected fgets() fail\n");
		    }
		    fclose($handle);
		}
	}
}
