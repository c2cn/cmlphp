<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-211 下午2:23
 * @version  2.5
 * cml框架 分页类
 * *********************************************************** */
namespace Cml\Vendor;

use Cml\Config;
use Cml\Http\Input;
use Cml\Route;

class Page
{
    //分页栏每页显示的页数
    public $rollPage = 5;
    //页数跳转时要带的参数
    public $param;
    //分页url地址
    public $url = '';
    //列表每页显示条数
    public $listRows;
    //起始行数
    public $firstRow;
    //分页总页数
    protected $totalPages;
    //总行数
    protected $totalRows;
    //当前页数
    protected $nowPage;
    //分页栏的总页数
    protected $coolPages;
    //分页变量名
    protected $varPage;
    //分页定制显示
    protected $config = array(
        'header' => '条记录',
        'prev' => '上一页',
        'next' => '下一页',
        'first' => '第一页',
        'last' => '最后一页',
        'theme' => '<li><a>%totalRow% %header% %nowPage%/%totalPage%页</a></li>%upPage% %downPage% %first%  %prePage% %linkPage%  %nextPage%  %end%'
    );

    /**
     * 构造函数
     *
     * @param int $totalRows 总行数
     * @param int $listRows 每页显示条数
     * @param string $param分页跳转时带的参数
     *
     * @return void
     */
    public function __construct($totalRows, $listRows = 20, $param = '')
    {
        $this->totalRows = $totalRows;
        $this->listRows = $listRows ? intval($listRows) : 10;
        $this->varPage = Config::get('VAR_PAGE') ? Config::get('VAR_PAGE') : 'p';
        $this->param = $param;
        $this->totalPages = ceil($this->totalRows/$this->listRows);
        $this->coolPages = ceil($this->totalPages/$this->rollPage);
        $this->nowPage = Input::getInt($this->varPage, 2);
        if ($this->nowPage < 1) {
            $this->nowPage = 1;
        } elseif (!empty($this->totalRows) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows*($this->nowPage - 1);
    }

    /**
     * 配置参数
     *
     * @param string $name 配置项
     * @param string $value 配置的值
     *
     * @return void
     */
    public function setConfig($name, $value)
    {
        isset($this->config[$name]) && ($this->config[$name] = $value);
    }

    /**
     *输出分页
     */
    public function show()
    {
        if ($this->totalRows == 0)  return '';
        $p = $this->varPage;
        $nowCoolPage = ceil($this->nowPage/$this->rollPage);
        $depr = \Cml\Config::get('url_pathinfo_depr');
        if ($this->url) {
            $url = rtrim(\Cml\Http\Response::Url($this->url.'/__PAGE__', $this->param, false), $depr);
        } else {
            $addUrl = \Cml\Config::get('APP_MODULE') ? CML_MODULE_NAME.'/' : '';
            $this->param = array_merge($this->param, array($p => '__PAGE__'));
            $url = rtrim(\Cml\Http\Response::Url($addUrl.Route::$urlParams['controller'].'/'.Route::$urlParams['action'], $this->param, false), $depr);
        }
        $upRow = $this->nowPage - 1;
        $downRow = $this->nowPage + 1;
        $upPage = $upRow > 0 ? '<li><a href = "'.str_replace('__PAGE__', $upRow, $url).'">'.$this->config['prev'].'</a></li>' : '';
        $downPage  = $downRow <= $this->totalPages ? '<li><a href="'.str_replace('__PAGE__', $downRow, $url).'">'.$this->config['next'].'</a></li>' : '';

        // << < > >>
        if ($nowCoolPage == 1) {
            $theFirst = $prePage = '';
        } else {
            $preRow = $this->nowPage - $this->rollPage;
            $prePage = '<li><a href="'.str_replace('__PAGE__', $preRow, $url).'">上'.$this->rollPage.'页</a></li>';
            $theFirst = '<li><a href="'.str_replace('__PAGE__', 1, $url).'">'.$this->config['first'].'</a></li>';
        }

        if ($nowCoolPage == $this->coolPages) {
            $nextPage = $theEnd = '';
        } else {
            $nextRow = $this->nowPage + $this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = '<li><a href="'.str_replace('__PAGE__', $nextRow, $url).'">下'.$this->rollPage.'页</a></li>';
            $theEnd = '<li><a href="'.str_replace('__PAGE__', $theEndRow, $url).'">'.$this->config['last'].'</a></li>';
        }

        //1 2 3 4 5
        $linkPage = '';
        for ($i = 1; $i <= $this->rollPage; $i++) {
            $page = ($nowCoolPage -1) * $this->rollPage + $i;
            if ($page != $this->nowPage) {
                if ($page <= $this->totalPages) {
                    $linkPage .= '&nbsp;<li><a href="'.str_replace('__PAGE__', $page, $url).'">&nbsp;'.$page.'&nbsp;</a></li>';
                } else {
                    break;
                }
            } else {
                if ($this->totalPages != 1) {
                    $linkPage .= '&nbsp;<li class="active"><a>'.$page.'</a></li>';
                }
            }
        }
        $pageStr = str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd),
            $this->config['theme']
        );
        return '<ul>'.$pageStr.'</ul>';
    }
}