<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\UtilsBundle\Tests\Driver;

use Contao\Model;
use Contao\System;
use HeimrichHannot\UtilsBundle\Driver\DC_Table_Utils;
use HeimrichHannot\UtilsBundle\Model\CfgTagModel;
use HeimrichHannot\UtilsBundle\Tests\TestCaseEnvironment;

class DC_Table_UtilsTest extends TestCaseEnvironment
{
    public function setUp()
    {
        parent::setUp();

        $container = System::getContainer();

        $adapter = $this->mockAdapter(['getParams']);
        $adapter->method('getParams')->willReturn([]);
        $container->set('doctrine.dbal.default_connection', $adapter);

        $modelUtilsAdapter = $this->mockAdapter(['findModelInstanceByPk']);
        $modelUtilsAdapter->method('findModelInstanceByPk')->willReturn($this->createMock(Model::class));
        $container->set('huh.utils.model', $modelUtilsAdapter);

        System::setContainer($container);

        if (!interface_exists('listable')) {
            include_once __DIR__.'/../../vendor/contao/core-bundle/src/Resources/contao/helper/interface.php';
        }
    }

    public function testInstantiation()
    {
        $this->createGlobalDca('table');
        $dcTableUtils = new DC_Table_Utils('table');
        $this->assertInstanceOf(DC_Table_Utils::class, $dcTableUtils);
    }

    public function testCreateFromModel()
    {
        $result = DC_Table_Utils::createFromModel($this->getModel());
        $this->assertInstanceOf(DC_Table_Utils::class, $result);
    }

    public function testCreateFromModelData()
    {
        $result = DC_Table_Utils::createFromModelData(['id' => 12], 'table', 'field');
        $this->assertInstanceOf(DC_Table_Utils::class, $result);
    }

    /**
     * @return Model | \PHPUnit_Framework_MockObject_MockObject
     */
    public function getModel()
    {
        $this->createGlobalDca('tl_cfg_tag');
        $model = new CfgTagModel();

        return $model;
    }

    public function createGlobalDca($table)
    {
        $GLOBALS['TL_DCA'][$table] = [
            'config' => [
                'dataContainer' => 'Table',
                'ptable' => 'ptable',
                'ctable' => ['tl_content', 'ctable'],
                'enableVersioning' => true,
                'onsubmit_callback' => [],
                'oncopy_callback' => [],
                'onload_callback' => [],
                'sql' => [
                    'keys' => [
                        'id' => 'primary',
                    ],
                ],
            ],
            'list' => [
                'label' => [
                    'fields' => ['title'],
                    'format' => '%s',
                ],
                'sorting' => [
                    'mode' => 1,
                    'fields' => ['title'],
                    'headerFields' => ['title'],
                    'panelLayout' => 'filter;sort,search,limit',
                    'root' => [],
                ],
                'global_operations' => [
                    'all' => [
                        'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                        'href' => 'act=select',
                        'class' => 'header_edit_all',
                        'attributes' => 'onclick="Backend.getScrollOffset();"',
                    ],
                ],
                'operations' => [
                    'edit' => [
                        'label' => &$GLOBALS['TL_LANG']['table']['edit'],
                        'href' => 'table=tl_content&ptable=table',
                        'icon' => 'edit.gif',
                    ],
                ],
            ],
            'palettes' => [
                'default' => '{general_legend},title;',
            ],

            'subpalettes' => [],
            'fields' => [
                'id' => [
                    'sql' => 'int(10) unsigned NOT NULL auto_increment',
                ],
                'pid' => [
                    'foreignKey' => 'ptable.id',
                    'sql' => "int(10) unsigned NOT NULL default '0'",
                    'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
                ],
                'title' => [
                    'label' => &$GLOBALS['TL_LANG']['table']['title'],
                    'exclude' => true,
                    'search' => true,
                    'inputType' => 'text',
                    'eval' => ['maxlength' => 255, 'tl_class' => 'w50', 'mandatory' => true],
                    'sql' => "varchar(255) NOT NULL default ''",
                ],
            ],
        ];
    }
}
