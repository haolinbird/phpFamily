<?php
/**
 * Response class file.
 * @author Su Chao<chaos@jumei.com>
 */

/**
 * This class provides a series of methods to handle responses to clients.
 */
class JMResponse
{
    /**
     * Send json/jsonp data and http header.
     *
     * @param mixed $data
     * @param string $callback
     */
    public static function json($data, $callback=null)
    {
        $data = json_encode($data);
        if($callback)
        {
            $data = $callback.'('.$data.');';
            $header = 'Content-type: text/javascript; utf-8;';
        }
        else
        {
            $header = 'Content-type: application/json; utf-8;';
        }
        header($header);
        echo $data;
    }
}