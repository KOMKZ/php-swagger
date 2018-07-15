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
	protected static function getSwgCliPath(){
		return dirname(dirname(__FILE__)) . "/vendor/zircote/swagger-php/bin/swagger";
	}
	protected function getContentFromPhpFile(){
		return file_get_contents($this->getPhpFile());
	}
	protected function writeToPhpFile($content){
		file_put_contents($this->getPhpFile(), $content . "\n\n", FILE_APPEND);
	}
	protected function resetPhpFile(){
		file_put_contents($this->getPhpFile(), "");
	}
	protected function getPhpFile(){
		return sprintf("/tmp/swg-%s.php", date("ymd", time()));
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
					'description' => '',
					'more' => []
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
	    $this->resetPhpFile();
		$rootDocs = [];
		foreach($this->docs['root'] as $item){
			$itemDoc = $item[0];
			$rootDocs[$itemDoc['name']] = $itemDoc['value'];
		}
		$this->writeToPhpFile(static::buildRoot($rootDocs));

		foreach($this->docs['defs'] as $defItem){
			if($result = static::buildDef($defItem)){
				$this->writeToPhpFile($result);
			}
		}


		$i = 0;
		while(isset($this->docs['apis'][$i]) && ($apiDoc = $this->docs['apis'][$i])){
			$apiReturn = $this->docs['apis'][$i+1];
			$apiDoc = static::buildOneApi($apiDoc, $apiReturn);
			$this->writeToPhpFile($apiDoc);
			$i += 2;
		}
	}

	public function geneJsonSwg(){
		$swgCli = static::getSwgCliPath();
		$cmd = sprintf("%s %s --output %s", $swgCli, $this->getPhpFile(), $this->saveFile);
		echo "{$cmd}\n";
		system($cmd);
	}

	static protected $custDefs = [];
	public static function buildDef($defItem){
		$def = $defItem[0];
		$defName = $def['value']['def'];
		$propStr = [];
		foreach ($def['value']['more'] as $prop) {
			if($prop['type'] == 'cust'){
				// cust类型不用存储，作为模版使用
				static::$custDefs[$defName] = $defItem;
				return "";
			}
			$propStr[] = static::buildOneProp($prop);
		}

		$tpl = sprintf("/**\n*  @SWG\Definition(\n*    definition=\"{$defName}\",\n%s\n*  )\n*/",
		implode(",\n", $propStr)
		);
		return $tpl;
	}
	public static function buildOneApi($apiDocItem, $apiReturnItem){
		$apiDoc = $apiDocItem[0];
		$apiReturn = $apiReturnItem[0];

		// 解析参数
		$paramStr = [];
		$bodyParamProps = [];
		foreach($apiDoc['value']['more'] as $param){
			if($param['path_type'] == 'body'){
				$bodyParamProps[] = $param;
			}else{
				$paramStr[] = static::buildCommParameter($param);
			}
		}
		if($bodyParamProps){
			$paramStr[] = static::buildBodyParameter($bodyParamProps);
		}
		$paramStr = implode(",\n", $paramStr);
		// 解析return
		$defName = $apiReturn['value']['def'];
		$targetReturnDef = static::$custDefs[$defName];
		// 合并target
		foreach($apiReturn['value']['more'] as $prop){
			foreach($targetReturnDef[0]['value']['more'] as $index => $tProp){
				if($prop['name'] == $tProp['name']){
					$targetReturnDef[0]['value']['more'][$index] = array_merge($tProp, $prop);
				}
			}
		}
		$returnDoc = sprintf("*   	@SWG\Response(\n*   		response=200,\n*   		description=\"\",\n%s\n*   	)",
					static::buildPropsSchema($targetReturnDef[0]['value']['more']));
		$tpl = <<<tpl
/**
 *  @SWG\%s(
 *    path="%s",
 *    tags={"%s"},
 *    summary="%s",
 *    produces={"application/json"},%s
 %s
 *  )
 */
tpl;
		return sprintf($tpl,
		ucfirst($apiDoc['value']['method'])
		,$apiDoc['value']['path']
		,$apiDoc['value']['tag']
		,$apiDoc['value']['description']
		,$paramStr ? "\n{$paramStr}," : ""
		,$returnDoc
		);

	}

	public static function buildCommParameter($param){
		$attrs = [];
		if($param['required'] == 'required'){
			$attrs[] = "*         required=true";
		}else{
			$attrs[] = "*         required=false";
		}

		if($param['type'] == 'integer'){
			$attrs[] = "*         type=\"integer\"";
			$attrs[] = "*         format=\"int32\"";
		}else{
			// boolean string
			$attrs[] = "*          type=\"{$param['type']}\"";;
		}


		if($param['enums'] != 'not_enums'){
			$enums = static::getEnumsFromStr($param['enums']);
			$enumStr = implode("\",\"", $enums);
			$attrs[] = "*          enums={\"{$enumStr}\"}";
		}
		$attrs[] = "*         name=\"{$param['name']}\"";


		$attrs[] = "*         in=\"{$param['path_type']}\"";

		$attrs[] = "*         description=\"{$param['description']}\"";
		$tpl = sprintf("*     @SWG\Parameter(\n%s\n*     )", implode(",\n", $attrs));
		return $tpl;
	}
	public static function buildBodyParameter($props){
		$attrs = [];
		$attrs[] = "*         name=\"body\"";
		$attrs[] = "*         in=\"body\"";
		$propStr = [];
		$requiredPropStr = [];
		foreach ($props as $prop) {
			if($prop['required'] == "required"){
				$requiredPropStr[] = $prop['name'];
			}
			$propStr[] = static::buildOneProp($prop);
		}
		$requiredPropStr = implode("\",\"", $requiredPropStr);
		$attrs[] = "*         required={\"{$requiredPropStr}\"}";
		$attrs[] = sprintf("*    @SWG\Schema(\n%s\n)", implode(",\n", $propStr));
		$result = sprintf("*     @SWG\Parameter(\n%s\n*     )", implode(",\n", $attrs));
		return $result;
	}
	public static function buildPropsSchema($props){
		$propStr = [];
		foreach ($props as $prop) {
			$propStr[] = static::buildOneProp($prop);
		}
		return sprintf("*    @SWG\Schema(\n%s\n)", implode(",\n", $propStr));
	}
	public static function buildOneProp($prop){
		$attrs = [];
		if($prop['type'] == 'integer'){
			$attrs[] = "*         type=\"integer\"";
			$attrs[] = "*         format=\"int32\"";
		}elseif(preg_match('/(?<type>object|array)#(?<ref>.+)/', $prop['type'], $matches)){
			if($matches['type'] == 'object'){
				$attrs[] = "*         type=\"object\"";
				$attrs[] = "*      ref=\"#/definitions/{$matches['ref']}\"";
			}else{
				$attrs[] = "*         type=\"array\"";
				$attrs[] = "*      @SWG\Items(\n*      type=\"object\",\n*      ref=\"#/definitions/{$matches['ref']}\"\n*    )";
			}
		}else{
			// boolean string
			$attrs[] = "*          type=\"{$prop['type']}\"";;
		}
		$attrs[] = "*         description=\"{$prop['description']}\"";
		$attrs[] = "*         property=\"{$prop['name']}\"";
		$tpl = sprintf("*     @SWG\Property(\n%s\n*     )", implode(",\n", $attrs));
		return $tpl;
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
	public static function getEnumsFromStr($enumStr){
		return ["a", "b", "d"];
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
