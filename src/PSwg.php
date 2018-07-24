<?php
namespace pswg;

/**
 *
 */
class PSwg
{
	public function saveFromParser(PSwgParser $parser){
		PSwgParser::buildEnumsFromFile($parser->enumsFile);
		$parser->parse();
		$parser->genePhpSwg();
		$parser->geneJsonSwg();
	}
}
