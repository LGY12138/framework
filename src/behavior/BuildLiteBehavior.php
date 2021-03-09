<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace behavior;

// 创建Lite运行文件
// 可以替换框架入口文件运行
// 建议绑定位置app_init
use think\Hook as Hook;

class BuildLiteBehavior
{
    /**
     * @param $params
     */
    public function run(&$params)
    {
        if (!defined('BUILD_LITE_FILE')) {
            return;
        }

        $litefile = C('RUNTIME_LITE_FILE', null, RUNTIME_PATH . 'lite.php');
        if (is_file($litefile)) {
            return;
        }

        $defs = get_defined_constants(true);
        $content = 'namespace {$GLOBALS[\'_beginTime\'] = microtime(TRUE);';
        if (MEMORY_LIMIT_ON) {
            $content .= '$GLOBALS[\'_startUseMems\'] = memory_get_usage();';
        }

        // 生成数组定义
        unset($defs['user']['BUILD_LITE_FILE']);
        $content .= $this->buildArrayDefine($defs['user']) . '}';

        // 读取编译列表文件
        $filelist = [
            THINK_PATH . 'helper.php',
            APP_PATH . 'helper.php',
            CORE_PATH . 'Hook' . EXT,
            CORE_PATH . 'App' . EXT,
            CORE_PATH . 'Log' . EXT,
            CORE_PATH . 'log/driver/File' . EXT,
            CORE_PATH . 'Route' . EXT,
            CORE_PATH . 'Controller' . EXT,
            CORE_PATH . 'Storage' . EXT,
            CORE_PATH . 'storage/driver/File' . EXT,
            CORE_PATH . 'Exception' . EXT,
        ];

        // 编译文件
        foreach ($filelist as $file) {
            if (is_file($file)) {
                $content .= compile($file);
            }
        }

        // 处理Think类的start方法
        $content = preg_replace('/\$runtimefile = RUNTIME_PATH(.+?)(if\(APP_STATUS)/', '\2', $content, 1);
        $content .= "\nL(" . var_export(L(), true) . ");\nC(" . var_export(C(), true) . ');think\Hook::import(' . var_export(\think\Hook::get(), true) . ');think\Think::start();}';

        // 生成运行Lite文件
        file_put_contents($litefile, strip_whitespace('<?php ' . $content));
    }

    /**
     * 根据数组生成常量定义
     * @param $array
     * @return string
     */
    private function buildArrayDefine($array)
    {
        $content = "\n";
        foreach ($array as $key => $val) {
            $key = strtoupper($key);
            $content .= 'defined(\'' . $key . '\') or ';
            if (is_int($val) || is_float($val)) {
                $content .= "define('" . $key . "'," . $val . ');';
            } elseif (is_bool($val)) {
                $val = ($val) ? 'true' : 'false';
                $content .= "define('" . $key . "'," . $val . ');';
            } elseif (is_string($val)) {
                $content .= "define('" . $key . "','" . addslashes($val) . "');";
            }
            $content .= "\n";
        }
        return $content;
    }
}
