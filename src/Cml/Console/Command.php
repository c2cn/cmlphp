<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-10-15 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 命令行工具-命令抽象类
 * *********************************************************** */

namespace Cml\Console;

use Cml\Console\Format\Colour;
use Cml\Console\Format\Format;
use Cml\Console\IO\Output;

/**
 * 控制台命令抽象类
 *
 * @package Cml\Console
 */
abstract class Command
{

    /**
     * Console实例
     *
     * @var Console
     */
    protected $console;

    /**
     * Command constructor.
     *
     * @param Console $console
     */
    public function __construct($console)
    {
        $this->console = $console;
    }

    /**
     * 命令的入口方法
     *
     * @param array $args 传递给命令的参数
     * @param array $options 传递给命令的选项
     */
    abstract public function execute(array $args, array $options = []);

    /**
     * 格式化文本
     *
     * @param string $text 要格式化的文本
     * @param array $option 格式化选项 @see Format
     *
     * @return string
     */
    public function format($text, $option = [])
    {
        $format = new Format($option);
        return $format->format($text);
    }

    /**
     * 格式化输出
     *
     * @param string $text 要输出的内容
     * @param array $option 格式化选项 @see Format
     *
     * @return $this
     */
    public function write($text, $option = [])
    {
        Output::write($this->format($text, $option));
        return $this;
    }

    /**
     * 格式化输出并换行
     *
     * @param string $text 要输出的内容
     * @param array $option 格式化选项 @see Format
     *
     * @return $this
     */
    public function writeln($text, $option = [])
    {
        Output::writeln($this->format($text, $option));
        return $this;
    }

    /**
     * 格式化输出并换行-黄色
     *
     * @param string $text 要输出的内容
     * @param bool $highlight 是否高亮
     *
     * @return $this
     */
    public function warn($text, $highlight = false)
    {
        return $this->writeln($text, ['foregroundColors' => $highlight ? [Colour::YELLOW, Colour::HIGHLIGHT] : [Colour::YELLOW]]);
    }

    /**
     * 格式化输出并换行-红色
     *
     * @param string $text 要输出的内容
     * @param bool $highlight 是否高亮
     *
     * @return $this
     */
    public function error($text, $highlight = false)
    {
        return $this->writeln($text, ['foregroundColors' => $highlight ? [Colour::RED, Colour::HIGHLIGHT] : [Colour::RED]]);
    }

    /**
     * 格式化输出并换行-绿色
     *
     * @param string $text 要输出的内容
     * @param bool $highlight 是否高亮
     *
     * @return $this
     */
    public function info($text, $highlight = false)
    {
        return $this->writeln($text, ['foregroundColors' => $highlight ? [Colour::GREEN, Colour::HIGHLIGHT] : [Colour::GREEN]]);
    }
}
