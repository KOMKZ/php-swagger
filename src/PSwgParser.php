<?php
namespace pswg;


/**
 * 简化版本的php-swagger书写解析器
 */
class PSwgParser
{
	/**
	 * 保存的swagger json文件地址
	 * @var string
	 */
	public $saveFile = '';

	/**
	 * 输入的定义文件地址，即简化的php-swagger写法
	 * @var string
	 */
	public $inputFile = '';

	/**
	 * 运行模式
	 * @var string
	 */
	public $mode = 'debug';

	/**
	 * 美剧值定义文件
	 * @var string
	 */
	public $enumsFile = "";

	/**
	 * 解析文档存储数组
	 * @var array
	 */
	protected $docs = [
		'root' => [],
		'apis' => [],
		'defs' => [],
		'params' => [],
		'validators' => [],
	];

	/**
	 * 文档解析块是否开始
	 * @var array
	 */
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

	/**
	 * 获取有效的解析属性
	 * @return array 返回属性列表
	 *
	 * 该返回是一个数组，数组的key是有效的属性名称，值是包含解析属性解析器parser和所属分类的belong的元素
	 */
	protected static function getValidProps(){
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

	/**
	 * 计算获取得到php-swagger的命令的执行路径
	 * @return string 返回命令的执行路径
	 */
	protected static function getSwgCliPath(){
		return dirname(dirname(__FILE__)) . "/vendor/zircote/swagger-php/bin/swagger";
	}

	/**
	 * 获取php-swagger.php的文件内容
	 * @return string 返回文件内容
	 */
	protected function getContentFromPhpFile(){
		return file_get_contents($this->getPhpFile());
	}

	/**
	 * 将解析生成的内容写入到php-swagger.php文件中
	 * @param  string $content 解析得到的内容
	 */
	protected function writeToPhpFile($content){
		file_put_contents($this->getPhpFile(), $content . "\n\n", FILE_APPEND);
	}

	/**
	 * 置空php-swagger.php的文件内容
	 */
	protected function resetPhpFile(){
		file_put_contents($this->getPhpFile(), "<?php\n");
	}

	/**
	 * 获取php-swagger.php的文件的保存路径
	 * @return string 返回文件的保存路径
	 */
	protected function getPhpFile(){
		return sprintf("/tmp/swg-%s.php", date("ymd", time()));
	}

	/**
	 * 构建错误调试信息
	 * @param  integer $line 当前解析到的行数
	 * @param  string $value 当前解析到的内容
	 * @return string        返回调试信息
	 */
	protected static function buildErrorString($line, $value){
		return sprintf("error:%s %s", $line, $value);
	}

	/**
	 * 获取属性值的解析器列表
	 * @return array 返回解析器列表
	 */
	protected static function getValueParser(){
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

	/**
	 * 获取解析器支持的属性名称
	 * @see getValidProps()
	 * @return array 返回支持的属性名称列表
	 */
	protected static function getValidPropNames(){
		return array_keys(static::getValidProps());
	}

	/**
	 * 调试工具
	 * @param  string $value 调试信息
	 */
	protected function debug($value){
		if($this->mode == 'debug'){
			echo $value;
		}
	}


	/**
	 * 保存属性的解析结果
	 */
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

	/**
	 * 置空属性解析容器
	 */
	protected function resetDocProp(){
		$this->propBegin = false;
		$this->propName = "";
		$this->propValue = ['more' => []];
		$this->end = true;
		$this->propMoreValue = [];
	}

	/**
	 * 保存一个文档解析结果
	 */
	protected function pushDocBlock(){
		foreach($this->docBlock as $type => $items){
			foreach($items as $item){
				$this->docs[$type][] = $item;
			}
		}
		$this->debug("推入一个doc\n\n");
	}

	/**
	 * 置空一个文档解析容器
	 */
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

	/**
	 * 开始解析文本
	 */
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

	/**
	 * 将解析内容保存到php-swagger.php文件中
	 */
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

		foreach ($this->docs['params'] as $defItem) {
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

	/**
	 * 执行命令保存swagger.json文件
	 */
	public function geneJsonSwg(){
		$swgCli = static::getSwgCliPath();
		$cmd = sprintf("%s %s --output %s", $swgCli, $this->getPhpFile(), $this->saveFile);
		echo "{$cmd}\n";
		system($cmd);
	}

	/**
	 * 定义模板，不做实际定义用
	 * @var array
	 */
	static protected $custDefs = [];

	/**
	 * 将定义的内容转成php-swagger的定义
	 * @see getValueParser() 参考该方法了解def解析返回的内容
	 * @param  array $defItem 简写的定义内容
	 * @return string         返回php-swagger定义
	 */
	protected static function buildDef($defItem){
		$def = $defItem[0];
		$defName = $def['value']['def'];
		$propStr = [];
		$requiredProps = [];
		foreach ($def['value']['more'] as $prop) {
			if($prop['type'] == 'cust'){
				// cust类型不用存储，作为模版使用
				static::$custDefs[$defName] = $defItem;
				return "";
			}
			if(isset($prop['required'])){
				$requiredProps[] = $prop['name'];
			}
			$propStr[] = static::buildOneProp($prop);
		}
		$attrs = [];
		$attrs[] = sprintf("*    definition=\"{$defName}\"", $defName);
		$attrs[] = implode(",\n", $propStr);
		if($requiredProps){
			$attrs[] = sprintf("*	required={\"%s\"}", implode("\",\"", $requiredProps));
		}

		$tpl = sprintf("/**\n*  @SWG\Definition(\n%s*)\n*/",
		implode(",\n", $attrs)
		);
		return $tpl;
	}

	/**
	 * 将api的内容转成php-swagger的api
	 * @param  array $apiDocItem  api定义信息
	 * @param  array $apiReturnItem api的return定义信息
	 * @return string 返回php-swagger的api定义内容
	 */
	protected static function buildOneApi($apiDocItem, $apiReturnItem){
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
		$returnDoc = sprintf("*   	@SWG\Response(\n*   		response=200,\n*   		description=\"\",\n%s\n*)\n",
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

	/**
	 * 将常规参数转成php-swagger的参数定义
	 * @see getValueParser() 了解参数的结构
	 * @param  array $param 参数
	 * @return string        返回php-swagger参数定义
	 */
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
			$attrs[] = "*          enum={\"{$enumStr}\"}";
		}
		$attrs[] = "*         name=\"{$param['name']}\"";


		$attrs[] = "*         in=\"{$param['path_type']}\"";

		$attrs[] = "*         description=\"{$param['description']}\"";
		$tpl = sprintf("*     @SWG\Parameter(\n%s\n*     )", implode(",\n", $attrs));
		return $tpl;
	}


	/**
	 * 将body参数转成php-swagger的参数定义
	 * @see getValueParser() 了解参数的结构
	 * @param  array $param 参数
	 * @return string        返回php-swagger参数定义
	 */
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
		$attrs[] = sprintf("*    @SWG\Schema(\n%s,\n%s\n*)",
			"*         required={\"{$requiredPropStr}\"}",
			implode(",\n", $propStr)
		);
		$result = sprintf("*     @SWG\Parameter(\n%s\n*     )", implode(",\n", $attrs));
		return $result;
	}

	/**
	 * 构建属性组合实体
	 * @see getValueParser() 了解属性的结构
	 * @param  array $props 参数
	 * @return string        返回php-swagger属性定义
	 */
	public static function buildPropsSchema($props){
		$propStr = [];
		foreach ($props as $prop) {
			$propStr[] = static::buildOneProp($prop);
		}
		return sprintf("*    @SWG\Schema(\n%s\n*)", implode(",\n", $propStr));
	}

	/**
	 * 将属性定义装成php-swagger的属性定义
	 * @param  array $prop 属性定义
	 * @return string      返回php-swagger属性定义
	 */
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
		if(!empty($prop['enums']) && 'not_enums' != $prop['enums']){
			$enumsDes = static::getEnumsDesFromStr($prop['enums']);
		}else{
			$enumsDes = '';
		}
		$attrs[] = "*         description=\"{$prop['description']} {$enumsDes}\"";
		$attrs[] = "*         property=\"{$prop['name']}\"";
		$tpl = sprintf("*     @SWG\Property(\n%s\n*     )", implode(",\n", $attrs));
		return $tpl;
	}

	/**
	 * 将根定义转为php-swagger定义
	 * @param  array $rootDocs 根定义信息
	 * @return string          返回php-swagger根定义信息
	 */
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


	/**
	 * 获取枚举值数组
	 * @param  string $enumStr 枚举值的名称
	 * @return array          枚举值列表
	 */
	public static function getEnumsFromStr($enumStr, $line = 0){
		if(preg_match('/enums\((?<name>[a-zA-Z0-9\-\_]+)\)/', $enumStr, $matches)){
			return static::$enumsValue[$matches['name']];
		}else{
			throw new \Exception(static::buildErrorString($line, $enumStr));
		}
	}

	public static function getEnumsDesFromStr($enumStr, $line = 0){
		if(preg_match('/enums\((?<name>[a-zA-Z0-9\-\_]+)\)/', $enumStr, $matches)){
			return static::$enums[$matches['name']];
		}else{
			console($enumStr, 1);
			throw new \Exception(static::buildErrorString($line, $enumStr));
		}
	}


	static protected $enums = [];
	static protected $enumsValue = [];

	public static function buildEnumsFromFile($file){
		if(file_exists($file)){
			if(!static::$enums){
				$data = spyc_load_file($file);
				$result = [];
				foreach($data as $item){
					$enums = [];
					foreach($item['items'] as $value){
						$def = explode('|', $value);
						$def['value'] = $def[0];
						$def['name'] = $def[1];
						$def['des'] = isset($def[2]) ? $def[2] : "";
						$def['symbol'] = isset($def[3]) ? $def[3] : "";
						$def['isdefault'] = isset($def[4]) ? $def[4] : "";
						$value = $def;
						static::$enumsValue[$item['field']][] = $value['value'];
						$enums[] = sprintf("%s:%s", $value['value'], $value['name']);
					}
					$result[$item['field']] = implode('|', $enums);
				}
				static::$enums = $result;
			}
			return static::$enums;
		}else{
			return [];
		}
	}



	/**
	 * 检查解析行是否是doc的结束
	 * @param  string $buffer 解析的一行内容
	 * @return boolean 当以*\/结束是返回true
	 */
	public static function checkIsDocEnd($buffer){
		return preg_match("/\s*\*\/\s*\n/", $buffer);
	}

	/**
	 * 检查解析行是否是doc
	 * @param  string $buffer 解析的一行内容
	 * @return boolean 当以\/**开始是返回true
	 */
	public static function checkIsDocBegin($buffer){
		return preg_match("/^\s*\/\*\*\s*\n/", $buffer);
	}

	/**
	 * 检查是否是doc内容
	 * @param  string $buffer 解析的一行内容
	 * @return boolean 满足条件时为true
	 */
	public static function checkIsDoc($buffer){
		return preg_match("/@(?<name>[a-zA-Z\-\_]+)\s+(?<value>.*)/", $buffer) ||
			   preg_match("/\*\s+\-\s+(?<name>[a-zA-Z\-\_]+)\s+(?<value>.+)[\s\n]*/", $buffer) ||
			   preg_match("/\*\s+\-\s+\{.+\}$/", $buffer);
	}
}
