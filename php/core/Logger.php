<?php
/**
 * Contains Logger class 
 *
 * @author Rob Griggs
 */

/**
 * Critical class reconfigures native behavior of PHP to log errors and exceptions to database 
 * - also provides service to rest of application for exception logging
 */
class Logger
{
	/** @var self|null $instance container for singleton instance of Logger */
    private static $instance = null;
	
	/** @var PDO $loggingDB database connection to logging database */
    private static $loggingDB;
	
	/** @var string $transactionID unique identifier created at appliation startup */ 
    private static $transactionID;    
        
	/**
	 * Private constructor used to create singleton instance of class: 
	 * 
	 * Will attmpt to write a php.log and call die() if failes to initialize.
	 * Sets new error handler to call Logger::errorhandler(),
	 * registers new shutdown function as Logger::check_for_fatal_error(),
	 * sets new exception handler to call Logger->uncaughtExceptionHandler()
	 *
	 * @param PDO $db database connection to logging database
	 * @param string $transactionID unique identifier created at application startup
	 *  
	 */
    private function __construct(PDO $db, $transactionID)
    {
        if($db === null OR $transactionID === null){
            $msg = 'Error: Could not initialize logger.';
			error_log($msg);
            die();
        }
        
        self::$loggingDB = $db;
        self::$transactionID = $transactionID;
        
        set_error_handler(array($this, 'errorHandler'));
        register_shutdown_function('Logger::check_for_fatal_error');
        set_exception_handler(array($this,'uncaughtExceptionHandler'));
    }
    
	/**
	 * Singleton, returns class if stored, attemps to call constructor and initilize if not stored
	 * 
	 * @param PDO|null $loggingDB connection to logging database
	 * @param string $transactionID unique identifier created at application startup
	 */
    public static function get_instance(PDO $loggingDB = null, $transactionID = null)
    {
        if( self::$instance === null ){
            self::$instance = new self($loggingDB, $transactionID);
            return self::$instance;
        }
        return self::$instance;
    }
    
	/**
	 * Defines handling and application behavior when built in PHP errors, warning, ect.. are encountered
	 * logs errors to database
	 * 
	 * @param int $errno error number
	 * @param string $errstr error string
	 * @param string $errfile file where error originated
	 * @param string $errline line where error originated
	 * 
	 * @return void
	 */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if(error_reporting() == 0){
            return;
        }
   
       $db = self::$loggingDB;
       $transactionID = self::$transactionID;

        $query = "INSERT INTO errorlog (transaction_id, severity, message, filename, lineno) VALUES ( ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
     
        switch ($errno) {
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_STRICT:
                $stmt->execute(array($transactionID, "NOTICE", $errstr, $errfile, $errline));
                echo PHP_EOL . '<br><b> NOTICE: </b>' . $errstr . ' file: ' . $errfile . ' at line: ' . $errline ;
                break;
     
            case E_WARNING:
            case E_USER_WARNING:
                $stmt->execute(array($transactionID,"WARNING", $errstr, $errfile, $errline));
                echo PHP_EOL . '<br><b> WARNING: </b>' . $errstr . ' file: ' . $errfile . ' at line: ' . $errline;
                break;
     
            case E_ERROR:
            case E_USER_ERROR:
                $stmt->execute(array($transactionID, "FATAL", $errstr, $errfile, $errline));
                echo PHP_EOL . '<br><b> FATAL: </b>' . $errstr . ' file: ' . $errfile . ' at line: ' . $errline;
                exit("FATAL error $errstr at $errfile:$errline");
     
            default:
                $msg = "Unknown error at $errfile:$errline";
                $stmt->execute(array($transactionID, "Unknown Error", $errstr, $errfile, $errline));
                echo PHP_EOL . '<br><b> Unknown Error: </b>' . $errstr . ' file: ' . $errfile . ' at line: ' . $errline;
                exit("Unknown error at $errfile:$errline");
        }
    }

	/**
	 * Registered to run when php shuts down, attempts to capture the last error that occured and log it
	 * 
	 * @return void
	 */
    public static function check_for_fatal_error()
    {
        $error = error_get_last();
        if ( $error["type"] === E_ERROR OR $error['type'] === E_USER_ERROR OR $error['type'] == E_PARSE){

            $db = self::$loggingDB;
            $transactionID = self::$transactionID;
            $query = "INSERT INTO errorlog (transaction_id, severity, message, filename, lineno) VALUES ( ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);

            echo PHP_EOL . '<br><b> FATAL: </b>' . $error["message"] . ' file: ' . $error['file'] . ' at line: ' . $error['line'];
            $stmt->execute(array($transactionID, 'FATAL: SHUTDOWN', $error["message"], $error["file"], $error["line"]));
        }
    }

	/**
	 * Registered to define handling of uncaught exceptions
	 * 
	 * @param Exception $exception the uncaught exception
	 * 
	 * @return void
	 */
    function uncaughtExceptionHandler(Exception $exception)
    {
        $db = self::$loggingDB;
        $transactionID = self::$transactionID;
        
        $query = "INSERT INTO errorlog (transaction_id, severity, message, filename, lineno) VALUES ( ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);

        $msg = $exception->__toString();
        
        $stmt->execute(array($transactionID,"UNCAUGHT EXCEPTION", $msg, '', ''));
        
        echo 'UNCAUGHT EXCEPTION: '.$msg;
    }
    
    /**
	 * Used to log an exception in the logging db
	 * 
	 * @param Exception $exception exception to be logged
	 * 
	 * @return void
	 */
    function logException(Exception $exception)
    {
        $db = self::$loggingDB;
        $transactionID = self::$transactionID;
        
        $query = "INSERT INTO errorlog (transaction_id, severity, message, filename, lineno) VALUES ( ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);

        $msg = $exception->__toString();
        
        $stmt->execute(array($transactionID,"CAUGHT EXCEPTION", $msg, '', ''));
        
        //echo 'CAUGHT EXCEPTION: '.$msg;
    }

	/**
	 * main error handler calls this function to log a known error to the logging db
	 * 
	 * @param string $message error message
	 * 
	 * @return void
	 */
    function log_known_error($message)
    {
        
        if(!isset($this->transaction_id)){
            $this -> transaction_id = $this -> createTransactionID($this->databaseName, $this->program, $this->logging_db);
        }
        
        if(is_array($message)){
            $holder = serialize($message);
            $message = $holder;
        }

        //echo PHP_EOL.$this -> databaseName . ': ' . $message . PHP_EOL;

        $success = $this->update_logging_databased($this -> logging_db, 'caught', $message);

        $file = 'error.txt';

        $id = $this -> databaseName . ': on ' . date(DATE_RFC2822) . PHP_EOL;

        file_put_contents($file, $id, FILE_APPEND | LOCK_EX);

        $message = '     ' . $message . PHP_EOL . PHP_EOL;

        file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
    }

}
