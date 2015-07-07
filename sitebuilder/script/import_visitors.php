<?php
use meumobi\sitebuilder\repositories\VisitorsRepository;
use meumobi\sitebuilder\entities\Visitor;
use meumobi\sitebuilder\services\ImportVisitorsCsvService;

require dirname(__DIR__) . '/config/cli.php';

$options = getopt('f:s:', ['password-strategy::', 'exclusive', 'resend']);

$site = Model::load('Sites')->firstById($options['s']);

require 'segments/' . $site->segment . '/config.php';
$options['resend'] = isset($options['resend']);
$import = new ImportVisitorsCsvService();
if (isset($options['exclusive'])) $import->setMethod(ImportVisitorsCsvService::EXCLUSIVE);
if (@$options['password-strategy']) $import->setPasswordStrategy($options['password-strategy']);
$import->setSite($site);
$import->setFile($options['f']);
echo $import->import($options);
