<?php

class ArticleToImport
{
    public $articleCid;
    public $wp_publish;
    public $wp_postType;
    public $articleTitle;
    public $associatedMedia = array();

    public function __construct($articleCid, $articleTitle)
    {
        $this->articleCid = $articleCid;
        $this->articleTitle = $articleTitle;
    }

    public function get_articleCid()
    {
        return $this->articleCid;
    }

    public function get_articleTitle()
    {
        return $this->articleTitle;
    }

    public function get_wp_publish()
    {
        return $this->wp_publish;
    }

    public function set_wp_publish($value)
    {
        $this->wp_publish = $value;
    }

    public function get_wp_postType()
    {
        return $this->wp_postType;
    }

    public function set_wp_postType($value)
    {
        $this->wp_postType = $value;
    }

    public function get_associatedMedia()
    {
        return $this->associatedMedia;
    }
}

?>