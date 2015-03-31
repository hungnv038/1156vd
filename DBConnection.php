<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 3/31/15
 * Time: 13:43
 */
use Configs;

class DBConnection {
    public $con = '';

    private static $instance;

    function __construct(){

        $this->connect();
    }

    public static function getInstance() {
        if(self::$instance==null) {
            self::$instance=new DBConnection();
        }
        return self::$instance;
    }

    function connect(){

        try{

            $this->con = new PDO("mysql:host=".Configs::$DB_HOST.";dbname=".Configs::$DB_NAME,Configs::$DB_USER, Configs::$DB_PASS);
            $this->con->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

        }catch(PDOException $e){
            throw $e;
        }
    }
    public function Execute($query,$binding) {
        try {
            $sth = $this->con->prepare($query);
            $sth->execute($binding);
            $red = $sth->fetchAll();
            return $red;
        } catch(Exception $e) {
            throw $e;
        }
    }
}