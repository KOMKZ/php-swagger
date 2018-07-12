<?php
namespace pswg;

/**
 *
 */
class PSwg
{
	public function saveFromParser(PSwgParser $parser){
		$parser->parse();
		$parser->genePhpSwg();
	}
}
