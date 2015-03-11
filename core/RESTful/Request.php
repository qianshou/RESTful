<?php
namespace RESTful;
use RESTful\Exception\Request\ParsingJSON;
use RESTful\Util\OptionableArray;

/**
 * RESTful - Standalone RESTful server library
 * @author: Daniel Aranda (https://github.com/daniel-aranda/)
 *
 */

final class Request {

    const APPLICATION_JSON = 'application/json';
    const FORM_DATA = 'multipart/form-data';
    const FORM_URLENCODED = 'application/x-www-form-urlencoded';

    /**
     * @var string
     */
    private $path;

    /**
     * @var OptionableArray
     */
    private $server;

    /**
     * @var OptionableArray
     */
    private $post;

    /**
     * @var string
     */
    private $php_input;

    /**
     * @var string
     */
    private $request_method;

    /**
     * @var string
     */
    private $request_url;

    /**
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $arguments;

    public static function factory($path){
        $request = new Request(
            $path,
            new OptionableArray($_SERVER),
            new OptionableArray($_POST),
            file_get_contents("php://input")
        );

        return $request;
    }

    public function __construct(
        $path,
        OptionableArray $server,
        OptionableArray $post,
        $php_input
    ){
        $this->path = $path;
        $this->server = $server;
        $this->post = $post;
        $this->php_input = $php_input;

        $this->invalidate();
    }

    private function invalidate(
    ){

        $path = trim($this->path, '/');

        $arguments = explode('/', $path);

        $service = array_shift($arguments);

        $method = 'index';

        if( count($arguments) > 0 ){
            if( is_numeric($arguments[0]) ){
                $arguments[0] = (int) $arguments[0];
            }else{
                $method = array_shift($arguments);
            }
        }

        $request_method = $this->server->get('REQUEST_METHOD');
        $this->request_url = $this->path;
        $this->request_method = is_null($request_method) ? 'get' : strtolower($request_method);
        $this->service = $service;
        $this->method = $method;
        $this->arguments = $arguments;

    }

    public function getData(){
        $raw_data = $this->php_input;

        $data = [];

        if( $this->server->get('CONTENT_TYPE') === self::APPLICATION_JSON && !empty($raw_data) ){

            $data = @json_decode($raw_data, true);

            if( json_last_error() !== JSON_ERROR_NONE ){
                throw new ParsingJSON($raw_data);
            }
        }else if( $this->server->get('REQUEST_METHOD') === 'POST' ){
            $data = $this->post->source();
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getRequestMethod(){
        return $this->request_method;
    }

    /**
     * @return string
     */
    public function getService(){
        return $this->service;
    }

    /**
     * @return string
     */
    public function getMethod(){
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArguments(){
        return $this->arguments;
    }

}