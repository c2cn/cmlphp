<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2016/11/2 14:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 创建Entity命令
 * *********************************************************** */

namespace Cml\Console\Commands\Make;

use Cml\Cml;
use Cml\Config;
use Cml\Console\Command;
use Cml\Console\Format\Colour;
use Cml\Console\IO\Output;
use InvalidArgumentException;
use RuntimeException;

/**
 * 创建Entity
 *
 * @package Cml\Console\Commands\Make
 */
class Entity extends Command
{
    protected $description = "Create a new entity class";

    protected $arguments = [
        'name' => 'The name of the class'
    ];

    protected $options = [
        '--table=xx' => 'tablename',
        '--table_prefix=xx' => 'table prefix',
        '--dbconfig' => 'db config, default: `default_db`',
        '--template=xx' => 'Use an alternative template',
        '--dirname=xx' => 'the entity dir name default:`Entity`',
    ];

    protected $help = <<<EOF
this command allows you to create a new Entity class
eg:
`php index.php make:entity web/test-blog/Category --table=category`  this command will create a Entity

<?php
namespace web\test\Entity\blog;

/**
 * @property xx
 * ...
 */
class CategoryEntity extends Entity
{
    protected \$table = 'category';
}
EOF;


    /**
     * 创建Entity
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $tablePrefix = $options['table_prefix'] ?? Config::get('default_db.master.tableprefix');
        $tableName = $options['table'] ?? false;
        if (!$tableName) {
            throw new InvalidArgumentException(sprintf(
                'The option table "%s" is invalid. eg: user',
                $tableName
            ));
        }

        $tableInfo = \Cml\Model::getInstance($tableName, $tablePrefix, $options['dbconfig'] ?? 'default_db')->getDbFields($tableName, $tablePrefix, 0, true);

        $property = [];
        foreach ($tableInfo as $column) {
            $type = 'string';
            if (stripos($column['type'], 'int')) {
                $type = 'int';
            }
            array_push($property, " * @property {$type} \${$column['name']} {$column['comment']}");
        }

        $template = $options['template'] ?? false;
        $template || $template = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Entity.php.dist';
        $dirName = ($options['dirname'] ?? '') ?: 'Entity';

        list($namespace, $module) = explode('-', trim($args[0], '/\\'));
        if (!$module) {
            $namespace = explode('/', $namespace);
            $module = array_pop($namespace);
            $namespace = implode('/', $namespace);
        }

        if (!$namespace) {
            throw new InvalidArgumentException(sprintf(
                'The arg name "%s" is invalid. eg: web-Blog/Category',
                $args[0]
            ));
        }

        $path = Cml::getApplicationDir('apps_path') . DIRECTORY_SEPARATOR . $namespace . DIRECTORY_SEPARATOR
            . $dirName . DIRECTORY_SEPARATOR;
        $component = explode('/', trim(trim($module, '/')));

        if (count($component) > 1) {
            $className = ucfirst(array_pop($component)) . 'Entity';
            $component = implode(DIRECTORY_SEPARATOR, $component);
            $path .= $component . DIRECTORY_SEPARATOR;
            $component = '\\' . $component;
        } else {
            $className = ucfirst($component[0]) . 'Entity';
            $component = '';
        }

        if (!is_dir($path) && false == mkdir($path, 0700, true)) {
            throw new RuntimeException(sprintf(
                'The path "%s" could not be create',
                $path
            ));
        }

        $contents = strtr(file_get_contents($template), [
            '$property' => implode("\r\n", $property),
            '$namespace' => str_replace('/', '\\', $namespace),
            '$component' => $component,
            '$dirName' => $dirName,
            '$className' => $className,
            '$tableName' => $tableName
        ]);

        $file = $path . $className . '.php';
        if (is_file($file)) {
            throw new RuntimeException(sprintf(
                'The file "%s" is exist',
                $file
            ));
        }

        if (false === file_put_contents($file, $contents)) {
            throw new RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }

        $this->info("Entity created successfully. ");
    }
}
