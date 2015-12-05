<!doctype html>
<html>
<head>
    <meta charset='utf-8'>
    <title><?php echo \Cml\Lang::get('_CML_ERROR_');?></title>
    <style type="text/css">
        body{font-family:'微软雅黑';color:#333;}.link,.link a{text-align:center;text-decoration:none;color:#777;}.pure-table{border-collapse:collapse;border-spacing:0;empty-cells:show;border:1px solid #cbcbcb}.pure-table td,.pure-table th{border-left:1px solid #cbcbcb;border-width:0 0 0 1px;font-size:inherit;margin:0;overflow:visible;padding:6px 12px}.pure-table td:first-child,.pure-table th:first-child{border-left-width:0}.pure-table thead{background:#e0e0e0;color:#000;text-align:left;vertical-align:bottom}.pure-table td{background-color:transparent}.pure-table-odd td{background-color:#f2f2f2}
    </style>
</head>
<body>
<table class="pure-table">
    <thead>
        <tr>
            <th>系统发生错误</th>
        </tr>
    </thead>

    <tbody>
    <tr>
            <td style="font-size:50px;">:(</td>
        </tr>
    <?php if (isset($error['files'])) {?>
        <tr class="pure-table-odd">
            <td><b><?php echo strip_tags($error['message']);?></b></td>
        </tr>
        <tr>
            <td style="font-size:30px;">stack trace:</td>
        </tr>
        <?php foreach ($error['files'] as $val) {?>
            <tr>
                <td>
                    <b><?php echo \Cml\Lang::get('_ERROR_LINE_');?>:</b> <?php echo $val['file'] ;?>&#12288;LINE: <?php echo $val['line'];?>&#12288;->&#12288;【<?php echo (isset($val['class']) ? ($val['class'].$val['type']) : '').(isset($val['function']) ? $val['function']  : '');?>】
                    <?php echo \Cml\Debug::codeSnippet($val['file'], $val['line']);?>
                </td>
            </tr>
        <?php } }?>
        <tr class="pure-table-odd">
            <td class="link"><a href="#"></a></td>
        </tr>
    </tbody>
</table>
</body>
</html>