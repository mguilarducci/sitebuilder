<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <?php echo $this->html->charset() ?>
        <title><?php echo $title ?></title>
    </head>
    
    <body bgcolor="#EFEFEF">
        <font face="Arial" color="#555">
        <table border="0" width="640" align="center">
            <tr>
                <td><br /><br /><?php echo $this->html->imagelink(Mapper::url('/images/layout/logo.png', true), Mapper::url('/', true)) ?><br /><br /></td>
            </tr>
            <tr>
                <td bgcolor="#FFFFFF">
                    <?php echo $this->contentForLayout ?>
                </td>
            </tr>
        </table>
        </font>
    </body>
</html>