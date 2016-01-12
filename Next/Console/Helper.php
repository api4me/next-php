<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Helper.php
* @touch date Mon 07 Dec 2015 09:39:14 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Console;

class Helper {

    private $app;
    public function __construct() {
        $this->app = \Slim\Slim::getInstance();
    }
/*{{{ write */
    public function write($file, $content) {
        if (file_exists($file)) {
            printf("\033[31;1m [fail]\033[0m %s already exists.\n", $file);
            die();
        }

        $path = dirname($file);
        if (!file_exists($path)) {
            if (!mkdir($path, 0775, true)) {
                printf("\033[31;1m [fail]\033[0m Can't create the folder %s.\n", $path);
                die();
            }
        }

        if (file_put_contents($file, $content)) {
            printf("\033[32;1m [success]\033[0m %s has been created.\n", $file);
            return true;
        }
        printf("\033[31;1m [fail]\033[0m %s create fail.\n", $file);

        return false;
    }
/*}}}*/
/*{{{ rend */
    public function rend($tpl, $data) {
        $view = new \Next\Helper\Twig("./Next/Console/view");
        return $view->render($tpl, $data);
    }
/*}}}*/
/*{{{ columns */
    public function columns($table) {
        $out = array();

        $config = $this->app->config("mysql");
        $q = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE 
            FROM information_schema.`COLUMNS` 
            WHERE TABLE_SCHEMA LIKE :db AND TABLE_NAME LIKE :table;";
        $query = $this->app->db->prepare($q);
        $query->bindParam(":db", $config["dbname"]);
        $query->bindParam(":table", $table);
        $query->execute();

        while($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $out[] = $row["COLUMN_NAME"];
        }

        return $out;
    }
/*}}}*/

}
