<?php

class CXPRequest
{
    public static $http_code;

    public static function requestAuth($username, $password)
    {
        $header = array(
            CXPRequest::getHeaderAuth($username, $password)
        );

        $encPassword = base64_encode($password);

        $url = HttpUtils::$server
            . HttpUtils::$auth;

        return CXPRequest::requestGet($url, $header);
    }

    public static function getHeaderAuth($username, $password)
    {
        return HttpUtils::$headerAuth . base64_encode($username . ':' . $password);
    }

    private static function requestGet($url, $header)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $data = curl_exec($ch);
        $response = curl_getinfo($ch);

        if (curl_errno($ch)) {
            print curl_error($ch);
            throw new Exception('Error code: ' . $response['http_code'] . ' ' . 'Unable to load requested resource.');
        } else {
            curl_close($ch);
        }
        CXPRequest::$http_code = $response['http_code'];
        return $data;
    }

    public static function contentXpressSessionStart()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public static function contentXpressSessionGet($key, $default = '')
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return $default;
        }
    }

    public static function contentXpressSessionSet($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function doSearch($query_string, $start, $newPageLength, $options)
    {
        $header = array(
            'Content-type: application/xml;charset=utf-8',
            CXPRequest::getHeaderAuth(CXPRequest::contentXpressSessionGet("cxpuser"), CXPRequest::contentXpressSessionGet("cxppassword"))
        );
        $search = HttpUtils::$server
            . HttpUtils::$searchEndpoint
            . HttpUtils::$query
            . HttpUtils::$option
            . HttpUtils::$start
            . HttpUtils::$pageLength;

        $params = [HttpUtils::$query => $query_string,
            HttpUtils::$option => $options,
            HttpUtils::$start => $start,
            HttpUtils::$pageLength => $newPageLength];

        $url = HttpUtils::$server . HttpUtils::$searchEndpoint . '?' . http_build_query($params, '', "&");
        return CXPRequest::requestGet($url, $header);
    }

    public static function getArticle($uri, $transform)
    {
        $header = array(
            'Content-type: application/xml;charset=utf-8',
            CXPRequest::getHeaderAuth(CXPRequest::contentXpressSessionGet("cxpuser"), CXPRequest::contentXpressSessionGet("cxppassword"))
        );
        $params = [HttpUtils::$uri => $uri,
            HttpUtils::$transform => $transform];
        $url = HttpUtils::$server . HttpUtils::$articleEndpoint . '?' . http_build_query($params, '', "&");
        return CXPRequest::requestGet($url, $header);
    }

    public static function createQueryString($selectedPub, $selectedIssue, $selectedSection, $keywords, $newSort, $newPlatform, $newPublished)
    {
        $query_string = '';
        if ($selectedPub) {
            $query_string = 'publication:"' . $selectedPub . '"';
        }
        if ($selectedIssue) {
            $query_string .= ' issue:"' . $selectedIssue . '"';
        }
        if ($selectedSection) {
            $query_string .= ' section:"' . $selectedSection . '"';
        }
        if ($keywords) {
            $query_string .= ' ' . $keywords;
        }
        if ($newSort) {
            $query_string .= ' sort:' . $newSort;
        }

        if ($newPlatform) {
            $query_string .= ' originPlatform:' . $newPlatform;
        }

        if ($newPublished) {
            $query_string .= ' published:' . $newPublished;
        }

        return $query_string;
    }

    public static function getPublicationFacets($query_string)
    {
        return CXPRequest::doSearch($query_string, 1, 10, 'ext-publication-input');
    }

    public static function getIssueFacets($query_string)
    {
        return CXPRequest::doSearch($query_string, 1, 10, 'ext-issue-input');
    }

    public static function getSectionFacets($query_string)
    {
        return CXPRequest::doSearch($query_string, 1, 10, 'ext-section-input');
    }

    public static function getArticleResults($selectedPub, $selectedIssue, $selectedSection, $keywords, $newStart, $newPageLength, $newSort, $newPlatform, $newPublished)
    {
        return CXPRequest::doSearch(CXPRequest::createQueryString($selectedPub, $selectedIssue, $selectedSection, $keywords, $newSort, $newPlatform, $newPublished), $newStart, $newPageLength, 'cxp-query-options');
    }

    public static function searchAssociatedMediaData($media_query_string)
    {
        $header = array(
            'Content-type: application/xml;charset=utf-8',
            CXPRequest::getHeaderAuth(CXPRequest::contentXpressSessionGet("cxpuser"), CXPRequest::contentXpressSessionGet("cxppassword"))
        );

        $params = [HttpUtils::$query => $media_query_string,
            HttpUtils::$option => 'cxp-media-options',
            HttpUtils::$pageLength => '10000'];
        $url = HttpUtils::$server . HttpUtils::$searchMediaEndpoint . '?' . http_build_query($params, '', "&");
        return CXPRequest::requestGet($url, $header);
    }

    public static function createMediaQuery($articleCid)
    {
        $media_query_string = 'articleCid:"' . $articleCid . '"';
        return $media_query_string;
    }

    public static function getMediaData($articleCid)
    {
        return CXPRequest::searchAssociatedMediaData(CXPRequest::createMediaQuery($articleCid));
    }

    public static function createImportQuery()
    {
        $import_article = $_SESSION['importList'];
        foreach ($import_article as $articleCid => $articleToImport) {
            CXPRequest::importArticle($articleToImport);
        }
    }

    public static function importArticle($articleToImport)
    {
        $header = array(
            'Content-type: application/xml;charset=utf-8',
            CXPRequest::getHeaderAuth(CXPRequest::contentXpressSessionGet("cxpuser"), CXPRequest::contentXpressSessionGet("cxppassword"))
        );

        $params = [HttpUtils::$transform => HttpUtils::$articleToHtml];
        $url = HttpUtils::$server . HttpUtils::$articleEndpoint . '/' . $articleToImport->articleCid . '?' . http_build_query($params, '', "&");
        $data = CXPRequest::requestGet($url, $header);
        $articleXml = CXPRequest::data_to_xpath($data);
        $title = CXPRequest::get_article_title($articleXml);
        $author = CXPRequest::get_article_author($articleXml);

        $identifier = CXPRequest::get_article_part($articleXml, $queryStr = '//pam:message/pam:article/xhtml:head/dc:identifier');

        $filebroken = explode('.', $identifier);

        if (count($filebroken) > 1)
            $extension = array_pop($filebroken);

        $fileTypeless = implode('.', $filebroken);

        $node = new stdClass();
        $node->identifier = $fileTypeless;
        $node->title = $title;
        $node->author = $author;
        $node->type = 'article';
        $node->created = time();
        $node->date = CXPRequest::get_article_part($articleXml, $queryStr = '//pam:message/pam:article/xhtml:head/prism:publicationDate');
        $node->changed = $node->created;
        $node->status = $articleToImport->wp_publish;
        $node->post = $articleToImport->wp_postType;
        $node->promote = 1;
        $node->sticky = 0;
        $node->format = 1;
        $node->uid = 1;

        // Get the content and strip extraneous tags
        $body = CXPRequest::get_article_body($articleXml);
        $body = strip_tags( $body, '<p><h1><h2><h3><h4><h5><h6><code><pre><em><strong><i>' );
        $node->body = $body;

        $node->format = 'plain_text';
        $node->coverDisplayDate = CXPRequest::get_article_part($articleXml, $queryStr = '//pam:message/pam:article/xhtml:head/prism:coverDisplayDate');
        $node->section = CXPRequest::get_article_part($articleXml, $queryStr = '//pam:message/pam:article/xhtml:head/prism:section');

        CXPRequest::get_tag_info($articleXml, $node);
        CXPRequest::get_media_info($articleXml, $node);
        CXPRequest::get_rtf_terms($articleXml, $node);

        write_log( __METHOD__ . '() retrieving `' . $title . '`.' );

        return $node;
    }

    public static function data_to_xpath($data)
    {
        $xml = new SimpleXMLElement ($data);
        $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');
        $xml->registerXPathNamespace('pam', 'http://prismstandard.org/namespaces/pam/2.0/');
        $xml->registerXPathNamespace('pcv', 'http://prismstandard.org/namespaces/pcv/2.0/');
        $xml->registerXPathNamespace('pim', 'http://prismstandard.org/namespaces/pim/2.0/');
        $xml->registerXPathNamespace('prism', 'http://prismstandard.org/namespaces/basic/2.1/');
        $xml->registerXPathNamespace('prl', 'http://prismstandard.org/namespaces/prl/2.0/');
        $xml->registerXPathNamespace('pur', 'http://prismstandard.org/namespaces/prismusagerights/2.1/');
        $xml->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');
        $xml->registerXPathNamespace('entity', 'http://pubpress.com/temis/entity');
        $xml->registerXPathNamespace('RTF', 'http://pubpress.com/temis/entity/RTF');
        $xml->registerXPathNamespace('Terms', 'http://pubpress.com/temis/entity/RTF/Terms');

        return $xml;
    }

    private static function get_article_body($xml)
    {
        $queryStr = '//pam:message/pam:article/xhtml:body';
        $body = null;
        if ($xml instanceof SimpleXMLElement) {
            $bodyNodes = $xml->xpath($queryStr);
            $body = $bodyNodes[0]->asXML();
        }
        return $body;
    }

    private static function get_article_part($xml, $queryStr)
    {
        $body = null;
        if ($xml instanceof SimpleXMLElement) {
            $bodyNodes = $xml->xpath($queryStr);
            if (!empty ($bodyNodes))
                $body = (string)$bodyNodes[0];
        }
        return $body;
    }

    private static function get_article_title($xml)
    {
        $title = '';
        if ($xml instanceof SimpleXmlElement) {
            // We start from the root element to grab the alternateTitle
            $queryStr = '//pam:message/pam:article/xhtml:head/prism:alternateTitle';
            $altTitleNodes = $xml->xpath($queryStr); // $entries is an object of simpleXML of prism:alternateTitle nodes.
            // check if any of these nodes has attribute prism:platform set to web, if yes, pick that value as title.
            foreach ($altTitleNodes as $altTitleNode) {
                if ($altTitleNode instanceof SimpleXMLElement) { // this should always be true
                    $platform = $altTitleNode->xpath('@prism:platform');
                    if (!empty($platform) && $platform == 'web') {
                        $title = (string)$altTitleNode;
                        break;
                    }
                }
            }

            if ($title == '') { // if there no alternateTitle tag for web or if it was blank, grab dc:title
                $queryStr = '//pam:message/pam:article/xhtml:head/dc:title';
                $titleNodes = $xml->xpath($queryStr);
                if (!empty($titleNodes))
                    $title = (string)$titleNodes[0];
            }
        }
        return $title;
    }

    private static  function get_article_author($xml) {
        $author = '';
        if ($xml instanceof SimpleXmlElement) {
            $queryStr = '//pam:message/pam:article/xhtml:head/dc:creator';
            $authorNodes = $xml->xpath($queryStr);
            foreach ($authorNodes as $authorNode) {
                if ($authorNode instanceof SimpleXMLElement) {
                    $role = $authorNode->xpath('@prism:role');
                    if (!empty($role) && $role == 'author') {
                        $author = (string)$authorNode;
                        break;
                    }
                }
            }

            if ($author == '') { // if there no author role or if it was blank, grab first dc:creator
                $queryStr = '//pam:message/pam:article/xhtml:head/dc:creator';
                $authorNodes = $xml->xpath($queryStr);
                if (!empty($authorNodes))
                    $author = (string)$authorNodes[0];
            }
        }
        return $author;
    }

    private static function get_tag_info($xml, $articleNode)
    {
        if ($xml instanceof SimpleXmlElement) {
            $queryStr = '//pam:message/pam:article/xhtml:head/prism:keyword';
            $keywordNodes = $xml->xpath($queryStr);
            $ctr = 0;
            foreach ($keywordNodes as $keywordNode) {
                $articleNode->field_tags[$ctr] = $keywordNode->nodeValue;
                $ctr++;
            }

            if ($ctr == 0)
                $articleNode->field_tags = null;
        }
    }

    /**
     * Retrieves <RTF:Terms> from XML
     *
     * @see Function/method/class relied on
     * @link URL short description.
     * @global type $varname short description.
     *
     * @since x.x.x
     *
     * @param obj $xml Article XML.
     * @param obj $articleNode Article object.
     * @return void
     */
    public static function get_rtf_terms( $xml, $articleNode )
    {
        if( $xml instanceof SimpleXMLElement ){
            $queryStr = '//pam:message/entity:annotated/RTF:Terms';
            $termNodes = $xml->xpath( $queryStr );

            $ctr = 0;
            foreach ( $termNodes as $termNode ) {

                foreach( $termNode->attributes() as $a => $b ){
                    if( 'Terms' == $a )
                        $articleNode->term_tags[$ctr] = '' .$b;
                }

                $ctr++;
            }

            if ( 0 == $ctr )
                $articleNode->term_tags = array();
        }
    }

    private static function innerHTML($node)
    {
        if ($node instanceof SimpleXMLElement) {
            return $node->asXML();
        }
        return null;
    }

    private static function parseHeaders($headers)
    {
        $head = array();
        foreach ($headers as $k => $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1]))
                $head[trim($t[0])] = trim($t[1]);
            else {
                $head[] = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out))
                    $head['response_code'] = intval($out[1]);
            }
        }
        return $head;
    }

    private static function get_media_info($xml, $articleNode)
    {
        if ($xml instanceof SimpleXmlElement) {
            $images = $xml->xpath('//xhtml:img');
            $ctr = 0;

            foreach ($images as $img) {
                $contentID = (string)$img['id'];
                $title = (string)$img['src'];
                $altText = (string)$img['alt'];
                $caption = (string)$img['title'];
                $rank = $img->xpath('../xhtml:span/@id');

                if (!empty($rank))
                    $rank = str_replace('cxprank_', '', $rank);

                $articleNode->field_image[$ctr]['fid'] = $contentID;
                $articleNode->field_image[$ctr]['alt'] = $altText;
                $articleNode->field_image[$ctr]['title'] = $title;
                $articleNode->field_image[$ctr]['caption'] = $caption;
                $articleNode->field_image[$ctr]['rank'] = $rank;

                $ctr++;
            }
        }
    }

    public static function getImage($contentID)
    {
        $url = HttpUtils::$server . HttpUtils::$mediaEndpoint . $contentID;
        $header = stream_context_create(array(
            'http' => array(
                'header' => CXPRequest::getHeaderAuth(CXPRequest::contentXpressSessionGet("cxpuser"), CXPRequest::contentXpressSessionGet("cxppassword"))
            )
        ));

        $file = file_get_contents($url, false, $header);

        if ($file === false) {
            $file = file_get_contents(plugin_dir_path(__FILE__) . 'images/missing-image.png');
        } else {
            $filename = CXPRequest::parseHeaders($http_response_header)['Content-Disposition'];
            $filename = str_replace('filename=', '', $filename);
        }

        $image['file'] = $file;
        $image['filename'] = !empty($filename) ? $filename : 'missing-image.png';

        return $image;
    }

    public static function replaceImageAttr($imageID, $article, $imgArray)
    {
        $articleBody = $article->body;
        $articleBody = mb_convert_encoding($articleBody, 'HTML-ENTITIES', "UTF-8");
        $dom = new DOMDocument("1.0", "utf-8");
        $dom->loadHTML($articleBody);

        $requestImg = new DOMDocument();
        $requestImg->loadHTML($imgArray['request-size']);

        $requestImgNode = $requestImg->getElementsByTagName('img')->item(0);
        if ($requestImgNode instanceof DOMElement) {
            $requestImgURL = $requestImgNode->getAttribute('src');
            $reqImgClass = $requestImgNode->getAttribute('class');
            $fullImg = new DOMDocument();
            $fullImg->loadHTML($imgArray['full-size']);

            $fullImgNode = $fullImg->getElementsByTagName('img')->item(0);
            if ($fullImgNode instanceof DOMElement) {
                $fullImgURL = $fullImgNode->getAttribute('src');

                $domImgElement = $dom->getElementById($imageID);

                if (!is_null($domImgElement)) {
                    $domImgElement->setAttribute('src', $requestImgURL);
                    $domImgElement->setAttribute('class', 'aligncenter ' . $reqImgClass);
                    $domImgElement->parentNode->setAttribute('href', $fullImgURL);
                }
            }
        }
        $html_fragment = preg_replace(array('/<!DOCTYPE.+?>/', '/<\?xml.+?>/'), array('', ''), str_replace(array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $dom->saveHTML()));

        $article->body = $html_fragment;
    }
}

?>