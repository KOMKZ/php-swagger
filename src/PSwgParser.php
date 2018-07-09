<?php
namespace pswg;


/**
 *
 */
class PSwgParser
{
	public $saveFile = '';
	public $inputFile = '';
	public static function getValidProps(){
		return [
		   'swagger' => [
			   'parser' => 'single',
		   ],
		   'title' => [
			   'parser' => 'single',
		   ],
		   'description' => [
			   'parser' => 'single',
		   ],
		   'version' => [
			   'parser' => 'single',
		   ],
		   'host' => [
			   'parser' => 'single',
		   ],
		   'base_path' => [
			   'parser' => 'single',
		   ],
		   'schemes' => [
			   'parser' => 'single',
		   ],
		   'consumes' => [
			   'parser' => 'single',
		   ],
		   'produces' => [
			   'parser' => 'single',
		   ],
		   'api' => [
			   'parser' => 'api',
		   ],
		   'return' => [
			   'parser' => 'single',
		   ],
		   'def' => [
			   'parser' => 'single',
		   ],
		   'param' => [
			   'parser' => 'single',
		   ],
		   'validate' => [
			   'parser' => 'single',
		   ],
		];
	}
	public static function getValueParser(){
		return [
			'single' => function($value){
				return [$value, true];
			},
			'api' => function($value){
				$body = [
					'method' => '',
					'path' => '',
					'tag' => '',
					'description' => ''
				];
				list($body['method'], $body['path'], $body['tag'], $body['description']) = explode(',', $value);
				return [$body, false];
			}
		];
	}
	public static function getValidPropNames(){
		return array_keys(static::getValidProps());
	}
	public function parse(){
		$handle = @fopen($this->inputFile, "r");
		if ($handle) {
			$lineNum = 1;
			$docs = [];
			$names = static::getValidPropNames();
			$props = static::getValidProps();
			$valParsers = static::getValueParser();
			$docBegin = false;
			$propBegin = false;
			$propName = "";
			$propValue = [];
			$propMoreValue = [];
			$valParser = null;
			$end = true;
		    while (($buffer = fgets($handle, 4096)) !== false) {
		      	if(preg_match("/\s*\/\*\*\s*\n/", $buffer)){
					$docBegin = true;
				}
				//解析一行
				// @swagger 2.0.0
				if($docBegin){
					if(preg_match("/@(?<prop>[a-zA-Z\-\_]+)\s+(?<value>.*)/", $buffer, $matches)){
						if(!in_array($matches['prop'], $names)){
							throw new \Exception(sprintf("error:%s %s", $lineNum, $buffer));
						}
						if(!$end){
							$propValue['more'] = $propMoreValue;
							$docBlock[] = [[
								'prop' => $propName,
								'value' => $propValue
							], $lineNum];
							$propBegin = false;
							$propName = "";
							$propValue = [];
							$end = true;
							$propMoreValue = [];
						}
						$propName = $matches['prop'];
						$propValue = $matches['value'];
						$propBegin = true;
						$valParser = $valParsers[$props[$propName]['parser']];
						list($propValue, $end) = $valParser($propValue);
						if($end){
							$docBlock[] = [[
								'prop' => $propName,
								'value' => $propValue
							], $lineNum];
							$propBegin = false;
							$propName = "";
							$propValue = [];
							$end = true;
							$propMoreValue = [];
						}
					}elseif($propBegin){
						$propMoreValue[] = $buffer;
					}
				}
				if(preg_match("/\s*\*\/\s*\n/", $buffer)){
					$docs[] = $docBlock;
					// 置空
					$docBlock = [];
					$docBegin = false;
				}
				$lineNum++;
		    }
			console($docs);
		    if (!feof($handle)) {
		       	throw new \Exception("Error: unexpected fgets() fail\n");
		    }
		    fclose($handle);
		}
	}
}
