# php-swagger简化语法
本项目是[zircote/swagger-php]项目语法的简化写法，只实现其中的api部分，同时只支持一些关键属性，但是简化写法少了很多不必要的格式，使文档内容更加紧凑。

## 基本要求
PHP >= 5.3.2

## 安装
可以执行通过composer进行require，也可以修改composer.json文件进行安装：
```
{
    ...
    "require": {
        ...
        "lartik/php-swagger": "@dev"
    }
}
```
执行命令：
```
$ php composer.phar update
```

## 详细例子
参看 `./example/root-swagger.php`了解详细语法

## 开始使用
### 执行命令生成swagger.json
如下
```
$ ./pswg.php example/root-swagger.php /var/www/html/swagger.json example/enums.yaml
```
### 书写根定义
如下：
```php
/**
 * @swagger 2.0.0
 * @title User API
 * @description User API
 * @version 0.1.9
 * @host localhost:8064
 * @base_path
 * @schemes http
 * @consumes application/json
 * @produces application/json
 * @contact_url http://www.baidu.com
 * @contact_name kitral
 */
```

### 书写api定义
如下：
```php
/**
 * @api post,/users,User,创建一个用户
 * - u_name required,validate#u_name/default,string,body,not_def,not_enums,用户昵称
 * - u_info required,not_validate,object#u_info_param,body,not_def,not_enums,用户信息
 * - u_type required,not_validate,string,body,not_def,enums(u_type),用户类型
 * @return #global_res
 * - data object#user_item_object,用户数据
 */
```

### 书写model定义
如下：
```php
/**
* @def #user_item_object
* - u_id integer,用户id
* - u_type string,enums(u_type),用户类型
* - u_info object#u_info_object,用户信息对象
* - u_skills array#u_skill_object,用户技能对象列表
 */
```


### 书写param定义
如下：
```php
/**
* @param #u_info_param
* - u_address required,not_validate,string,skip,not_def,not_enums,用户地址
* - u_phone optional,not_validate,string,skip,not_def,not_enums,用户手机号码
 */
```

### 关于枚举值的语法
在例子中我们发现有两个地方用到了枚举值，第一个是在model中：

```php
/**
* @def #user_item_object
* - u_id integer,用户id
* - u_type string,enums(u_type),用户类型
* - u_info object#u_info_object,用户信息对象
* - u_skills array#u_skill_object,用户技能对象列表
 */
```
上述写法`enums(u_type)`会自动将枚举值的定义和label写入到描述中，另外一个如下：

```php
/**
 * @api post,/users,User,创建一个用户
 * - u_name required,validate#u_name/default,string,body,not_def,not_enums,用户昵称
 * - u_info required,not_validate,object#u_info_param,body,not_def,not_enums,用户信息
 * - u_type required,not_validate,string,body,not_def,enums(u_type),用户类型
 * @return #global_res
 * - data object#user_item_object,用户数据
 */
```
上述写法`enums(u_type)`会自动将枚举值放入swagger的限定值中，在swagger-ui中显示会下拉选项。

### 枚举值定义文件
文件格式使用yaml格式，格式定义例子如下：
```yaml
-
    name: 用户类型
    field: u_type
    items:
        - root|超级用户
        - admin|管理员用户
```
