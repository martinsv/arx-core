<?php defined('SYSPATH') or die('No direct script access.');

class cookie extends Kohana_Cookie {}

class c_cookie extends Kohana_Cookie
{
    /**
     * @var  string  Magic salt to add to the cookie
     */
    public static $salt = ZE_SALT;

    /**
     * @var  integer  Number of seconds before the cookie expires
     */
    public static $expiration = 0;

    /**
     * @var  string  Restrict the path that the cookie is available to
     */
    public static $path = '/';

    /**
     * @var  string  Restrict the domain that the cookie is available to
     */
    public static $domain = NULL;

    /**
     * @var  boolean  Only transmit cookies over secure connections
     */
    public static $secure = FALSE;

    /**
     * @var  boolean  Only transmit cookies over HTTP, disabling Javascript access
     */
    public static $httponly = FALSE;

}
