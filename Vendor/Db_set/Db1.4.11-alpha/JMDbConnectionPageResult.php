<?php
/**
 * 按照老框架中定义， 统一数据返回格式.
 */

namespace Db;

/**
 * 按照老框架中定义， 统一数据返回格式.
 *
 * @author XuRongYi<rongyix@jumei.com>
 * @date 2014-05-06
 */
class JMDbConnectionPageResult
{
    /**
     * 具体接口数据.
     */
    public $rows        = array();

    /**
     * 数据总条数.
     */
    public $rowCount    = 0;

    /**
     * 每页数据条数.
     */
    public $rowsPerPage = 0;

    /**
     * 当前页面.
     */
    public $pageIndex   = 0;

    /**
     * 页面数.
     */
    public $pageNumber  = 0;

    /**
     * 总页面数.
     */
    public $pageCount   = 0;
    
}