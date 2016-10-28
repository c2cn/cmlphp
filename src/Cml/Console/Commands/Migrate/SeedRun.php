<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 数据库迁移命令
 * 修改自https://github.com/robmorgan/phinx/tree/0.6.x-dev/src/Phinx/Console/Command
 * *********************************************************** */

namespace Cml\Console\Commands\Migrate;

use Cml\Console\Format\Colour;
use Cml\Console\Format\Format;
use Cml\Console\IO\Output;

/**
 * 数据库迁移-运行seed
 *
 * @package Cml\Console\Commands\Migrate
 */
class SeedRun extends AbstractCommand
{
    protected $description = "Run database seeders";

    protected $arguments = [
        'name' => 'What is the name of the seeder?',
    ];

    protected $options = [
        '--s=xxx | --seed=xxx' => 'What is the name of the seeder?',
    ];

    protected $help = <<<EOT
The seed:run command runs all available or individual seeders

phinx seed:run
phinx seed:run --seed=UserSeeder
phinx seed:run --s=UserSeeder
phinx seed:run  --s=UserSeeder --s=PermissionSeeder --s=LogSeeder

EOT;

    /**
     * 执行 seeders.
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $this->bootstrap($args, $options);

        $seedSet = isset($options['seed']) ? $options['seed'] : $options['s'];

        $format = new Format(['foregroundColors' => Colour::GREEN]);

        $config = $this->getConfig();
        $config = isset($config['migration_use_db']) ? $config[$config['migration_use_db']] : $config['default_db'];

        $driver = explode('.', $config['driver']);
        Output::writeln('using adapter ' . $format->format($driver[0]));

        Output::writeln($format->format('using database ') . $config['master']['name']);
        Output::writeln($format->format('using table prefix ') . $config['master']['tableprefix']);

        $start = microtime(true);

        if (empty($seedSet)) {
            // run all the seed(ers)
            $this->getManager()->seed();
        } else {
            is_array($seedSet) || $seedSet = [$seedSet];
            // run seed(ers) specified in a comma-separated list of classes
            foreach ($seedSet as $seed) {
                $this->getManager()->seed(trim($seed));
            }
        }

        $end = microtime(true);

        Output::writeln('');
        Output::writeln(Colour::colour('All Done. Took ' . sprintf('%.4fs', $end - $start), Colour::GREEN));
    }
}
