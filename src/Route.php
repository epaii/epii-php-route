<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2019/1/7
 * Time: 2:06 PM
 */

namespace epii\route;


use ReflectionMethod;

/**
 * @method void get(string $rule, $do) static
 * @method void post(string $rule, $do) static
 * @method void delete(string $rule, $do) static
 * @method void options(string $rule, $do) static
 * @method void put(string $rule, $do) static
 * @method void trace(string $rule, $do) static
 * @method void connect(string $rule, $do) static
 * @method void any(string $rule, $do) static
 */
class Route
{
    private static $pattern = [
        '*' => '/(.*)',
        '?' => '/([^\/]+)'
    ];

    private static $_matched = false;

    public static function __callStatic($name, $arguments)
    {
        self::rule($arguments[0], $arguments[1], strtoupper($name));
    }

    public static function rule(string $rule, $do, $http_type = "ANY")
    {
        if ($http_type === "ANY") {
            $http_type = ["POST", "GET", "DELETE", "OPTIONS", "PUT", "TRACE", "CONNECT"];
        }

        if (is_string($http_type)) {
            $http_type = [$http_type];
        }

        if (!in_array(strtoupper($_SERVER['REQUEST_METHOD']), $http_type)) {
            return;
        }

        if (self::$_matched) return;


        $url = $_SERVER["REQUEST_URI"];
        $names = [];
        $matchd = false;

        $rule = str_replace(["/*","/:"], ["/{__any__}:*","/{__any__}:"], $rule);
        $rule_origin = $rule;
        $rule = str_replace("/", "\\/", $rule);

        $rule_string = preg_replace_callback('/\/\{([a-z-0-9_]+)\}\??(:\(?[^\/]+\)?)?/i', function ($m) use (&$names, &$matchd) {
            // Check whether validation has been set and whether it exists.

            if (isset($m[2])) {
                $rep = substr($m[2], 1);

                $p = isset(self::$pattern[$rep]) ? self::$pattern[$rep] : '/' . $rep;
            } else {
                $p = self::$pattern['?'];
            }
            if (isset($m[1])) {
                $names[] = trim($m[1]);
            } else {
                $names[] = null;
            }

            $matchd = true;
            return  $p;
        }, trim($rule));


        if ($matchd) {


            if (stripos($rule_string, "\\") !== 0) {
                $rule_string = "\\" . $rule_string;
            }



            if (preg_match("/" . "^" . $rule_string . "/is", $url, $m)) {

                if ($m) {
                    array_shift($m);
                    $args = $m;
                    foreach ($m as $key => $value) {
                        if ($names[$key] == "__any__") continue;
                        if ($names[$key] !== null) {
                            $_REQUEST[$names[$key]] = $_GET[$names[$key]] = $args[$names[$key]] = $value;
                        }
                    }

                    self::w_m($args, $do);
                }
            }
        } else {

            if ($url == $rule_origin) {
                self::w_m([], $do);
            }
        }
    }

    private static function w_m($args, $do)
    {



        self::$_matched = true;
        if (is_callable($do)) {

            call_user_func_array($do, self::doFucntion(self::getFucntionParameterName($do), $args));
        } else if (is_string($do)) {

            $do = preg_replace_callback("/\{\\$(.*?)\}/is", function ($m) use ($args) {

                return isset($args[$m[1]]) ? $args[$m[1]] : "";
            }, $do);
            $do = preg_replace_callback("/\{(.*?)\}/is", function ($m) use ($args) {
                return isset($args[$m[1]]) ? $args[$m[1]] : "";
            }, $do);

            $class_array = explode("@", $do);
            if (class_exists($class_array[0])) {


                if (!isset($class_array[1])) {
                    $class_array[1] = "index";
                }

                $method = new ReflectionMethod($class_array[0], $class_array[1]);
                if ($method && $method->isPublic()) {
                    $depend = [];
                    foreach ($method->getParameters() as $value) {
                        $depend[] = $value->name;
                    }
                    $method->invokeArgs(new $class_array[0](), self::doFucntion($depend, $args));

                }


            }
        }
    }

    private static function doFucntion($_args_name, $args)
    {
        $_args = [];
        if (count($_args_name) > 0) {

            foreach ($_args_name as $key => $value) {
                if (isset($args[$value]))
                    $_args[$key] = $args[$value];
            }
        }
        if ( (!$_args) && (count($_args_name)==1))
        {
            $_args = [$args];
        }
        return $_args;
    }

    private static function getFucntionParameterName($func)
    {
        $ReflectionFunc = new \ReflectionFunction($func);
        $depend = array();
        foreach ($ReflectionFunc->getParameters() as $value) {
            $depend[] = $value->name;
        }
        return $depend;
    }

}