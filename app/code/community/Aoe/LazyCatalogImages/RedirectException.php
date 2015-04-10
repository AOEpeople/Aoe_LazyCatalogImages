<?php

class Aoe_LazyCatalogImages_RedirectException extends Exception
{
    protected $url;

    public function __construct($url, $message = "", $code = 0, Exception $previous = null)
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }
}
