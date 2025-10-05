<?php

class bxjsoncli_bxjsoncli extends CModule
{
    var $MODULE_ID = 'bxjsoncli.bxjsoncli';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;

    function __construct()
    {
        $arModuleVersion = array();

        include(__DIR__.'/version.php');


        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'Bitrix JSON CLI';
        $this->MODULE_DESCRIPTION = '';
        $this->PARTNER_NAME = '';

    }

    function DoInstall()
    {
        RegisterModule($this->MODULE_ID);
    }

    function DoUninstall()
    {
        UnRegisterModule($this->MODULE_ID);
    }
}
