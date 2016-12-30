<?php

class HttpUtils
{
    public static $server = 'https://contentapi.pubpress.com/capi/'; 			//PROD
    public static $auth = 'admin/authenticate';
    public static $headerAuth = 'Authorization: Basic ';


    public static $articleEndpoint = "article";
    public static $uri = "uri";
    public static $transform = "transform";
    public static $searchEndpoint = 'search';
    public static $searchMediaEndpoint = "search/media";
    public static $previewEndpoint = "/preview";
    public static $mediaEndpoint = "asset/";
    public static $query = "q";
    public static $option = "option";  //query options
    public static $start = "start";  //starting page
    public static $pageLength = "pageLength"; //number of results per page
    public static $articleToHtml = "article-to-html.xsl";
}

?>