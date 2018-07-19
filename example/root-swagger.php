<?php
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

/**
 * @api get,/users,User,获取用户列表接口
 * - u_id optional,not_validate,integer,query,not_def,not_enums,用户id
 * - u_type optional,not_validate,string,query,common,enums(u_type),用户类型
 * @return #global_res
 * - data object#user_list_object,返回用户列表
 */

/**
 * @api post,/users,User,创建一个用户
 * - u_name required,validate#u_name/default,string,body,not_def,not_enums,用户昵称
 * - u_info required,not_validate,object#u_info_param,body,not_def,not_enums,用户信息
 * - u_skills required,not_validate,array#u_skill_param,body,not_def,not_enums,用户技能列表
 * @return #global_res
 * - data object#user_item_object,用户数据
 */

 /**
  * @api put,/users/{id},User,修改一个用户
  * - id required,not_validate,integer,path,not_def,not_enums,用户id
  * - u_name required,validate#u_name/put,string,body,not_def,not_enums,用户昵称
  * - u_info required,not_validate,object#u_info_param,body,not_def,not_enums,用户信息
  * - u_skills required,not_validate,array#u_skill_param,body,not_def,not_enums,用户技能列表
  * @return #global_res
  * - data object#user_item_object,用户数据
  */

  /**
   * @api delete,/users/{id},User,修改一个用户
   * - id required,not_validate,integer,path,not_def,not_enums,用户id
   * @return #global_res
   * - data object#user_item_object,用户数据
   */

  /**
   * @def #global_res
   * - code integer,enums(u_type),提交状态
   * - data cust,返回的数据结构
   * - message string,错误信息
   *
   * @def #user_list_object
   * - total_count integer,总数量
   * - items array#user_item_object,用户列表
   *
   * @def #user_item_object
   * - u_id integer,用户id
   * - u_type string,enums(u_type),用户类型
   * - u_info object#u_info_object,用户信息对象
   * - u_skills array#u_skill_object,用户技能对象列表
   *
   * @def #u_info_object
   * - u_address string,用户居住地址
   * - u_phone string,用户手机号码
   *
   * @def #u_skill_object
   * - u_skill_age integer,技能时长
   * - u_skill_name string,技能名陈
   * - u_skill_level integer,技能等级
   *
   * @param #u_info_param
   * - u_address required,not_validate,string,skip,not_def,not_enums,用户地址
   * - u_phone optional,not_validate,string,skip,not_def,not_enums,用户手机号码
   *
   * @param #u_skill_param
   * - u_skill_age required,not_validate,string,skip,not_def,not_enums,用户地址
   * - u_skill_name required,not_validate,string,skip,not_def,not_enums,用户地址
   * - u_skill_level required,not_validate,string,skip,not_def,not_enums,用户地址
   *
   * @validate #u_name/put
   * - {"type":"string", "min":1, "max":2}
   *
   * @validate #u_name/default
   * - {"type":"unique","targetClass":"common\\models\\user\\ar\\User","targetAttribute":"u_name"}
   *
   */
