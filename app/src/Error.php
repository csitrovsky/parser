<?php


namespace app\src;


use Exception;
use RuntimeException;


use function header;
use function json_encode;


class Error
{
    
    /**
     * @var int
     */
    private $headers = JSON_PRETTY_PRINT;
    
    /**
     * @var array|string[]
     */
    private $status = [
        200 => 'Ok',
        204 => 'No Content',
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        500 => 'Internal Server Error',
    ];
    
    /**
     *
     */
    public function __construct()
    {
        
        $this->headers();
    }
    
    /**
     *
     */
    private function headers(): void
    {
        
        header('Content-Type: application/json');
    }
    
    /**
     * @throws Exception
     */
    public function throw($message, int $code = 200): void
    {
        
        if ($code !== 200) {
            $success = false;
        }
        throw new RuntimeException(
            $this->output([
                'success' => $success ?? true,
                'message' => $message,
            ], $code), $code
        );
    }
    
    /**
     * @param     $message
     * @param int $code
     *
     * @return false|float|int|mixed|string
     */
    public function output($message, int $code = 200)
    {
        
        header("HTTP/1.1 $code " . $this->request_status($code));
        
        return json_encode($message, $this->headers);
    }
    
    /**
     * @param int $code
     *
     * @return string
     */
    private function request_status(int $code): string
    {
        
        return ($this->status[$code]) ?: $this->status[500];
    }
}