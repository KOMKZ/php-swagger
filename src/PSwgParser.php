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
			   'belong' => 'root'
		   ],
		   'title' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'description' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'version' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'host' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'base_path' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'schemes' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'consumes' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'produces' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'contact_url' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'contact_name' => [
			   'parser' => 'single',
			   'belong' => 'root'
		   ],
		   'api' => [
			   'parser' => 'api',
			   'belong' => 'apis'
		   ],
		   'return' => [
			   'parser' => 'def',
			   'belong' => 'apis',
		   ],
		   'def' => [
			   'parser' => 'def',
			   'belong' => 'defs'
		   ],
		   'param' => [
			   'parser' => 'def',
			   'belong' => 'params'
		   ],
		   'validate' => [
			   'parser' => 'validate',
			   'belong' => 'validators'
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

	protected $docs = [
		'root' => [],
		'apis' => [],
		'defs' => [],
		'params' => [],
		'validators' => [],
	];
	protected $docBegin = false;
	protected $propBegin = false;
	protected $propName = '';
	protected $propValue = ['more' => []];
	protected $propMoreValue = [];
	protected $end = true;
	protected $lineNum = 1;
	protected $docBlock = [
		'root' => [],
		'apis' => [],
		'defs' => [],
		'params' => [],
		'validators' => [],
	];

	protected function pushDocProp(){
		if(!$this->propName){
			return 0;
		}
		if($this->propMoreValue){
			$this->propValue['more'] = $this->propMoreValue;
		}
		$props = static::getValidProps();
		$prop = $props[$this->propName];
		$belong = $prop['belong'];
		$this->docBlock[$belong][] = [[
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
		foreach($this->docBlock as $type => $items){
			foreach($items as $item){
				$this->docs[$type][] = $item;
			}
		}

		$this->debug("推入一个doc\n\n");
	}
	protected function resetDocBlock(){
		// 置空
		$this->docBlock = [
			'root' => [],
			'apis' => [],
			'defs' => [],
			'params' => [],
			'validators' => [],
		];
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
		    if (!feof($handle)) {
		       	throw new \Exception("Error: unexpected fgets() fail\n");
		    }
		    fclose($handle);
		}
	}

	public function genePhpSwg(){
		$rootDocs = [];
		foreach($this->docs['root'] as $item){
			$itemDoc = $item[0];
			$rootDocs[$itemDoc['name']] = $itemDoc['value'];
		}
		static::buildRoot($rootDocs);
		$i = 0;
		while(isset($this->docs['apis'][$i]) && ($apiDoc = $this->docs['apis'][$i])){
			$apiReturn = $this->docs['apis'][$i+1];
			static::buildOneApi($apiDoc, $apiReturn);
			$i += 2;
		}
	}
	public static function buildOneApi($apiDocItem, $apiReturnItem){
		$apiDoc = $apiDocItem[0];
		$apiReturn = $apiReturnItem[0];
		$bodyParams = [];
		$params = [];
		foreach($apiDoc['value']['more'] as $param){
			if($param['path_type'] == 'in_body'){
				$bodyParams[] = $param;
			}else{
				$params[] = $param;
			}
		}
		foreach($params as $param){
			static::buildOneParameter($param);
		}

		console($apiDoc);

	}
	public static function buildOneParameter($param){
		console($param);
	}
	public static function buildRoot($rootDocs){
		$root = <<<str
/**
 *  @SWG\Swagger(
 *    host="{$rootDocs['host']}",
 *    schemes={"{$rootDocs['schemes']}"},
 *    produces={"{$rootDocs['produces']}"},
 *    consumes={"{$rootDocs['consumes']}"},
 *    basePath="{$rootDocs['base_path']}",
 *    @SWG\Info(
 *      version="{$rootDocs['version']}",
 *      title="{$rootDocs['title']}",
 *      description="{$rootDocs['description']}",
 *      @SWG\Contact(name="{$rootDocs['contact_name']}", url="{$rootDocs['contact_url']}"),
 *      @SWG\License(name="{$rootDocs['contact_name']}", url="{$rootDocs['contact_url']}")
 *    )
 *  )
 */
str;
		return $root;
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
