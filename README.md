# php-swagger简化语法
## 内容
1. 参看 `./example/root-swagger.php`了解详细语法

## 开始
### 执行命令生成swagger.json
如下：
```
$ ./pswg.php user.php swg.json
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
