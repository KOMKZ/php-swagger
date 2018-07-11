<?php
namespace pswg;


/**
 *
 */
class PSwgParser
{
	public $saveFile = '';
	public $inputFile = '';
	public $mode = 'debug';
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
			   'parser' => 'def',
		   ],
		   'def' => [
			   'parser' => 'def',
		   ],
		   'param' => [
			   'parser' => 'def',
		   ],
		   'validate' => [
			   'parser' => 'validate',
		   ],
		];
	}
	public static function buildErrorString($line, $value){
		return sprintf("error:%s %s", $line, $value);
	}
	public static function getValueParser(){
		return [
			'single' => function($value, $line){
				return [$value, true];
			},
			'more_value' => function($value, $line){
				$isValidate = false;
				if(preg_match("/\*\s+\-\s+(?<value>\{.+\})$/", $value, $matches)){
					$isValidate = true;
				}elseif(!preg_match("/\*\s+-\s+(?<name>[a-zA-Z\-\_]+)\s+(?<value>.+)[\s\n]*/", $value, $matches)){
					throw new \Exception(static::buildErrorString($line, $value));
				}
				if($isValidate){
					$prop = json_decode($matches['value'], true);
					if(!$prop){
						throw new \Exception(static::buildErrorString($line, $value));
					}
					return [$prop, true];
				}


				// optional,not_validate,integer,in_query,not_def,not_enums,用户id
				$paramCount = count(explode(',', $matches['value']));
				if($paramCount == 7){
					$prop = [
						'name' => $matches['name'],
						'required' => '',
						'validator' => '',
						'type' => '',
						'path_type' => '',
						'default' => '',
						'enums' => '',
						'description' => '',
					];
					list(
						$prop['required'],
						$prop['validator'],
						$prop['type'],
						$prop['path_type'],
						$prop['default'],
						$prop['enums'],
						$prop['description'],
					) = explode(',', $matches['value']);
					return [$prop, true];
				}elseif($paramCount == 2 || $paramCount == 3){
					$prop = [
						'name' => $matches['name'],
						'type' => '',
						'enums' => '',
						'description' => '',
					];
					if($paramCount == 3){
						list(
							$prop['type'],
							$prop['enums'],
							$prop['description'],
						) = explode(',', $matches['value']);
					}else{
						list(
							$prop['type'],
							$prop['description'],
						) = explode(',', $matches['value']);
					}
					return [$prop, true];
				}
			},
			'def' => function($value, $line){
				list(,$name) = explode('#', $value);
				$body = [
					'def' => $name,
				];
				return [$body, false];
			},
			'validate' => function($value, $line){
				if(!preg_match("/#(?<name>[a-zA-Z\-\_]+)\/(?<scene>[a-zA-Z]+)/", $value, $matches)){

				}
				$body = [
					'param_name' => $matches['name'],
					'scene' => $matches['scene']
				];
				return [$body, false];
			},
			'api' => function($value, $line){
				$body = [
					'method' => '',
					'path' => '',
					'tag' => '',
					'description' => ''
				];
				list($body['method'], $body['path'], $body['tag'], $body['description']) = explode(',', $value);
				return [$body, false];
			},
		];
	}
	public static function getValidPropNames(){
		return array_keys(static::getValidProps());
	}
	public function debug($value){
		if($this->mode == 'debug'){
			echo $value;
		}
	}

	protected $docs = [];
	protected $docBegin = false;
	protected $propBegin = false;
	protected $propName = '';
	protected $propValue = ['more' => []];
	protected $propMoreValue = [];
	protected $end = true;
	protected $lineNum = 1;

	protected function pushDocProp(){
		if($this->propMoreValue){
			$this->propValue['more'] = $this->propMoreValue;
		}
		$this->docBlock[] = [[
			'name' => $this->propName,
			'value' => $this->propValue
		], $this->lineNum];
		$this->debug(sprintf("完成解析1@%s\n\n", $this->propName));
	}
	protected function resetDocProp(){
		$this->propBegin = false;
		$this->propName = "";
		$this->propValue = ['more' => []];
		$this->end = true;
		$this->propMoreValue = [];
	}
	protected function pushDocBlock(){
		$this->docs[] = $this->docBlock;
		$this->debug("推入一个doc\n\n");
	}
	protected function resetDocBlock(){
		// 置空
		$this->docBlock = [];
		$this->docBegin = false;
	}
	public function parse(){
		$handle = @fopen($this->inputFile, "r");
		if ($handle) {
			$names = static::getValidPropNames();
			$props = static::getValidProps();
			$valParsers = static::getValueParser();
		    while (($buffer = fgets($handle, 4096)) !== false) {
		      	if(static::checkIsDocBegin($buffer)){
					$this->docBegin = true;
				}
				//解析一行
				// @swagger 2.0.0
				if($this->docBegin){

					// 解析@ 开头的行
					if(preg_match("/@(?<name>[a-zA-Z\-\_]+)\s+(?<value>.*)/", $buffer, $matches)){
						if(!in_array($matches['name'], $names)){
							throw new \Exception(static::buildErrorString($this->lineNum, $buffer));
						}
						if(!$this->end){
							$this->pushDocProp();
							$this->resetDocProp();
						}
						$this->debug(sprintf("解析@%s\n", $matches['name']));

						$this->propName = $matches['name'];
						$this->propValue = $matches['value'];
						$this->propBegin = true;
						$valParser = $valParsers[$props[$this->propName]['parser']];
						list($this->propValue, $this->end) = $valParser($this->propValue, $this->lineNum);
						// 置空
						if($this->end){
							$this->pushDocProp();
							$this->resetDocProp();
						}
					}elseif($this->propBegin && static::checkIsDoc($buffer)){
						// 解析这一部分
						// * - u_id optional,not_validate,integer,in_query,not_def,not_enums,用户id
					    // * - u_type optional,not_validate,string,in_query,common,enums(u_type),用户类型
						$valParser = $valParsers['more_value'];
						list($value, ) = $valParser($buffer, $this->lineNum);
						$this->propMoreValue[] = $value;
						$this->debug(sprintf("解析%s->body\n", $this->propName));
					}
				}
				if(static::checkIsDocEnd($buffer)){
					$this->pushDocProp();
					$this->resetDocProp();
					$this->pushDocBlock();
					$this->resetDocBlock();
				}
				$this->lineNum++;
		    }
			console($this->docs);
		    if (!feof($handle)) {
		       	throw new \Exception("Error: unexpected fgets() fail\n");
		    }
		    fclose($handle);
		}
	}
	public static function checkIsDocEnd($buffer){
		return preg_match("/\s*\*\/\s*\n/", $buffer);
	}
	public static function checkIsDocBegin($buffer){
		return preg_match("/^\s*\/\*\*\s*\n/", $buffer);
	}
	public static function checkIsDoc($buffer){
		return preg_match("/@(?<name>[a-zA-Z\-\_]+)\s+(?<value>.*)/", $buffer) ||
			   preg_match("/\*\s+\-\s+(?<name>[a-zA-Z\-\_]+)\s+(?<value>.+)[\s\n]*/", $buffer) ||
			   preg_match("/\*\s+\-\s+\{.+\}$/", $buffer);
	}
}
