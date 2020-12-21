namespace php Provider.Lv1

/**
 * 服务说明 
 * 
 * @author
 * @copyright www.jumei.com 
 * 创建时间: 2016-3-16 15:30:11 
 */
service LiveThriftService
{
    /**
     * 首页获取直播列表
     * 
     * @param uid  未登陆用户，请传空字符串""
     * @
     */    
    string getLiveList(1: string uid, 2: string platform, 3: string version);

   
 /**
     * 获取直播列表
     * 
     * @uid  未登陆用户，请传空字符串""
     * @max 传入+inf
     * @size 每页显示数  默认为10
     * @type传"hot"
     */    
    string getAllLiveList(1:string uid,
                          2:string max,
                          3:string size,
                          4:string type,
                          5:string platform,
                          6:string version);

}