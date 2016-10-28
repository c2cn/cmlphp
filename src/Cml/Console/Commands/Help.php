<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-帮助命令
 * *********************************************************** */

namespace Cml\Console\Commands;

use Cml\Cml;
use Cml\Console\Command;
use Cml\Console\Format\Colour;
use Cml\Console\Format\Format;

/**
 * 默认的帮助命令
 *
 * @package Cml\Console\Commands
 */
class Help extends Command
{
    protected $description = "help command";

    protected $arguments = [
        'command' => 'input command to show command\'s help',
    ];


    /**
     * 执行命令入口
     *
     * @param array $args 参数
     * @param array $options 选项
     */
    public function execute(array $args, array $options = [])
    {
        $this->writeln("CmlPHP Console " . Cml::VERSION . "\n", ['foregroundColors' => [Colour::GREEN, Colour::HIGHLIGHT]]);

        $format = new Format(['indent' => 2]);
        $formatCommand = new Format(['indent' => 4]);

        $echoDefaultOptions = function ($command = '') use ($format) {
            $this->writeln("Options:");
            $this->writeln($format->format(Colour::colour('-h | --help', Colour::GREEN) . str_repeat(' ', 5) . "display {$command}command help info"));
            $this->writeln($format->format(Colour::colour('--no-ansi', Colour::GREEN) . str_repeat(' ', 7) . "disable ansi output"));
        };

        if (empty($args)) {
            $this->writeln("Usage:");
            $this->writeln($format->format("input 'command [options] [args]' to run command or input 'help command ' to display command help info\n"));

            $echoDefaultOptions();

            $this->writeln('');
            $this->writeln('Available commands:');

            $cmdGroup = [
                'no_group' => []
            ];
            foreach ($this->console->getCommands() as $name => $class) {
                if ($class !== __CLASS__) {
                    $class = new \ReflectionClass($class);
                    $property = $class->getDefaultProperties();
                    $property = isset($property['description']) ? $property['description'] : '';

                    $hadGroup = strpos($name, ':');
                    $group = substr($name, 0, $hadGroup);
                    $name = Colour::colour($name, Colour::GREEN);
                    $len = strlen($name);
                    $name .= str_repeat(' ', 25 - $len) . $property;
                    if ($hadGroup) {
                        $cmdGroup[$group][] = $name;
                    } else {
                        $cmdGroup['no_group'][] = $name;
                    }
                }
            }

            foreach ($cmdGroup['no_group'] as $cmd) {
                $this->writeln($formatCommand->format($cmd));
            }
            unset($cmdGroup['no_group']);

            foreach ($cmdGroup as $group => $cmdList) {
                $this->writeln($format->format($group));
                foreach ($cmdList as $cmd) {
                    $this->writeln($formatCommand->format($cmd));
                }
            }
        } else {
            $class = new \ReflectionClass($this->console->getCommand($args[0]));
            $property = $class->getDefaultProperties();
            $description = isset($property['description']) ? $property['description'] : '';
            $help = isset($property['help']) ? $property['help'] : false;
            $arguments = isset($property['arguments']) ? $property['arguments'] : [];
            $options = isset($property['options']) ? $property['options'] : [];

            $this->writeln("Usage:");
            $this->writeln($format->format("{$args[0]} [options] [args]\n"));

            $echoDefaultOptions('this ');

            if (count($options)) {
                foreach ($options as $option => $desc) {
                    $name = Colour::colour($option, Colour::GREEN);
                    $name .= str_repeat(' ', 25 - strlen($name)) . $desc;
                    $this->writeln($format->format($name));
                }
                $this->write("\n");
            }

            if (count($arguments)) {
                $this->writeln("Arguments");
                foreach ($arguments as $argument => $desc) {
                    $name = Colour::colour($argument, Colour::GREEN);
                    $name .= str_repeat(' ', 25 - strlen($name)) . $desc;
                    $this->writeln($format->format($name));
                }
            }
            $this->writeln("\nHelp:");
            $this->writeln($format->format($help ? $help : $description));
        }
        $this->write("\n");
    }
}
