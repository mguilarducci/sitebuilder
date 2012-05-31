<?php $currentSite = Auth::user()->site(); ?>
<!DOCTYPE html>
<html>
    <head>
        <?php echo $this->html->charset() ?>
        <title>MeuMobi - <?php echo $this->pageTitle ?></title>
        <link rel="shortcut icon" href="<?php echo Mapper::url('/images/layout/favicon.png') ?>" type="image/png" />
        <?php echo $this->html->stylesheet('shared/base', 'shared/uikit', 'shared/categories',
            'shared/edit-forms', 'shared/businessitems', 'segment', 'shared/markitup.simple',
            'shared/markitup.xbbcode', 'shared/chosen') ?>
        <?php echo $this->html->stylesForLayout ?>
    </head>

    <body>

        <div id="header">
            <div class="logo">
                <?php echo $this->html->imagelink('layout/logo.png', '/', array(
                    'alt' => 'MeuMobi'
                ), array(
                    'class' => 'logo'
                )) ?>
            </div>
            <div class="menu">
                <div class="navigation" id="navbar">
                    <div class="sites <?php echo (Users::ROLE_USER == $currentSite->role) ? 'solo' : '' ?>">
                        <p class="business-name"><span><?php echo e($currentSite->title) ?></span></p>
                        <p class="share">
                            http://<?php echo e($currentSite->domain) ?>
                            <!-- *
                            <a id="share_site" href="<?php echo e($currentSite->domain) ?>"><?php echo s('share url') ?></a> -->
                        </p>
                        <?php if (Users::ROLE_USER != $currentSite->role): ?>
                        <div class="site-switcher">
                            <p><?php echo s('My mobi sites');?></p>
                            <ul>
                                <li><a href="#">
                                    <span class="site-name">
                                        <span><?php echo e($currentSite->title) ?></span>
                                        <small>http://<?php echo e($currentSite->domain) ?></small>
                                    </span>
                                    <span class="status current"><?php echo s('current site')?></span>
                                </a></li>
                                <?php foreach (Auth::user()->sites(true) as $site): ?>
                                <li>
                                    <a href="<?php echo Mapper::url('/users/change_site/'.$site->id) ?>" >
                                        <span class="site-name">
                                            <span><?php echo e($site->title) ?></span>
                                            <small><?php echo e($site->domain) ?></small>
                                        </span>
                                        <span class="status edit"><?php echo s('edit site ›')?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li class="new"><a href="<?php echo Mapper::url('/sites/add') ?>"><?php echo s('Create a new mobi ›') ?></a></li>

                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="user">
                        <p><?php echo s('Hi <strong>%s</strong>', e(Auth::user()->firstname())) ?></p>
                        <ul>
                            <li><?php echo $this->html->link(s('My Account'), '/settings/account') ?></li>
                            <li><?php echo $this->html->link(s('Dashboard'), '/dashboard/index') ?></li>
                            <li><?php echo $this->html->link(s('Log out ›'), '/logout') ?></li>
                        </ul>
                        <!-- <?php echo $this->html->link(s('Log out ›'), '/logout') ?> -->
                    </div>
                </div>
                <ul>
                    <?php if(!$currentSite->hide_categories): ?>
                        <li><?php echo $this->html->link(e($currentSite->rootCategory()->title), '/categories') ?></li>
                    <?php endif ?>
                    <li><?php echo $this->html->link(s('Settings'), '/settings') ?></li>
                    <li><?php echo $this->html->link(s('Customization'), '/settings/customize') ?></li>
                    <?php if(Users::ROLE_ADMIN == $currentSite->role): ?>
                    <li><?php echo $this->html->link(s('Users'), '/sites/users') ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="clear"></div>
        </div>

        <?php echo $this->element('layouts/flash') ?>

        <div id="content">
            <?php echo $this->contentForLayout ?>
        </div>

        <?php echo $this->element('layouts/footer') ?>

        <?php echo $this->html->script('shared/jquery', 'shared/main', 'shared/markitup', 'shared/async_upload', 'shared/jquery.chosen') ?>
        <?php echo $this->html->scriptsForLayout ?>
    </body>
</html>
