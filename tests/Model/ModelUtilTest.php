<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\UtilsBundle\Tests\Model;

use Contao\ContentModel;
use Contao\Database;
use Contao\Model;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use HeimrichHannot\UtilsBundle\Container\ContainerUtil;
use HeimrichHannot\UtilsBundle\Dca\DcaUtil;
use HeimrichHannot\UtilsBundle\Model\CfgTagModel;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use HeimrichHannot\UtilsBundle\Tests\TestCaseEnvironment;

class ModelUtilTest extends TestCaseEnvironment
{
    protected $count = 0;

    public function setUpContaoFrameworkMock()
    {
        $framework = $this->mockContaoFramework();
        $framework->method('createInstance')->willReturnCallback(function ($type) {
            switch ($type) {
                case Database::class:
                    return $this->getMockBuilder(Database::class)->disableOriginalConstructor()->setMethods(null)->getMock();
            }
        });
    }

    public function testInstantiation()
    {
        $util = $this->createModelUtilMock();
        $this->assertInstanceOf(ModelUtil::class, $util);
    }

    public function testSetDefaultsFromDca()
    {
        $container = System::getContainer();
        $container->set('huh.utils.dca', new DcaUtil($this->mockContaoFramework()));

        $dbalAdapter = $this->mockAdapter(['getParams']);
        $container->set('doctrine.dbal.default_connection', $dbalAdapter);

        System::setContainer($container);

        error_reporting(E_ALL & ~E_NOTICE); //Report all errors except E_NOTICE

        $util = $this->createModelUtilMock();

        $GLOBALS['loadDataContainer']['tl_module'] = true;
        $GLOBALS['TL_DCA']['tl_module']['fields']['test'] = ['default' => 'test'];

        $model = $util->setDefaultsFromDca(new ModuleModel());

        $this->assertSame('test', $model->test);
    }

    public function testFindModelInstanceByPk()
    {
        $util = $this->createModelUtilMock();
        $this->assertNull($util->findModelInstanceByPk('tl_null', 5));
        $this->assertNull($util->findModelInstanceByPk('tl_null_class', 5));
        $this->assertNull($util->findModelInstanceByPk('tl_content', 4));
        $this->assertNull($util->findModelInstanceByPk('tl_content', 'content_null'));
        $this->assertSame(5, $util->findModelInstanceByPk('tl_content', 5)->id);
        $this->assertSame('alias', $util->findModelInstanceByPk('tl_content', 'alias')->alias);
    }

    public function testFindModelInstancesBy()
    {
        $util = $this->createModelUtilMock();
        $this->assertNull($util->findModelInstancesBy('tl_null', ['id'], [5]));
        $this->assertNull($util->findModelInstancesBy('tl_null_class', ['id'], [5]));
        $this->assertSame(5, $util->findModelInstancesBy('tl_content', ['id'], [5])->id);
        $this->assertSame(5, $util->findModelInstancesBy('tl_content', ['pid'], [3])->current()->id);
    }

    public function testFindOneModelInstanceBy()
    {
        $util = $this->createModelUtilMock();
        $result = $util->findOneModelInstanceBy('tl_content', [], []);
        $this->assertInstanceOf(ContentModel::class, $result);

        $result = $util->findOneModelInstanceBy('null', [], []);
        $this->assertNull($result);

        $result = $util->findOneModelInstanceBy('tl_cfg_tag', [], []);
        $this->assertNull($result);
    }

    public function testFindRootParentRecursively()
    {
        $modelAdapter = $this->mockAdapter([
            'getClassFromTable',
        ]);
        $modelAdapter->method('getClassFromTable')->with($this->anything())->willReturnCallback(function ($table) {
            switch ($table) {
                case 'tl_content':
                    return ContentModel::class;

                case 'tl_null_class':
                    return 'Huh\Null\Class\Nullclass';

                case 'tl_cfg_tag':
                    return CfgTagModel::class;

                case 'null':
                    return null;

                default:
                    return null;
            }
        });
        $contentModelAdapter = $this->mockAdapter([
            'findByPk',
        ]);
        $contentModelAdapter->method('findByPk')->willReturn($this->getModel(true));
        $util = new ModelUtil($this->mockContaoFramework([Model::class => $modelAdapter, ContentModel::class => $contentModelAdapter]), $this->createMock(ContainerUtil::class));
        $result = $util->findRootParentRecursively('id', 'tl_content', $this->getModel());
        $this->assertInstanceOf(\Contao\Model::class, $result);
    }

    public function testFindParentsRecursively()
    {
        $modelAdapter = $this->mockAdapter([
            'getClassFromTable',
        ]);
        $modelAdapter->method('getClassFromTable')->with($this->anything())->willReturnCallback(function ($table) {
            switch ($table) {
                case 'tl_content':
                    return ContentModel::class;

                case 'tl_null_class':
                    return 'Huh\Null\Class\Nullclass';

                case 'tl_cfg_tag':
                    return CfgTagModel::class;

                case 'null':
                    return null;

                default:
                    return null;
            }
        });
        $contentModelAdapter = $this->mockAdapter([
            'findByPk',
        ]);
        $contentModelAdapter->method('findByPk')->willReturn($this->getModel(true));
        $util = new ModelUtil(
            $this->mockContaoFramework([Model::class => $modelAdapter, ContentModel::class => $contentModelAdapter]),
            $this->createMock(ContainerUtil::class));
        $result = $util->findParentsRecursively('id', 'tl_content', $this->getModel());
        $this->assertInstanceOf(\PHPUnit_Framework_MockObject_MockObject::class, $result[0]);

        $result = $util->findParentsRecursively('id', 'tl_content', $this->getModel(true));
        $this->assertSame([], $result);
    }

    public function testFindModulePages()
    {
        $this->count = 0;
        $util = $this->createModelUtilMock();
        $module = $this->mockClassWithProperties(ModuleModel::class, ['id' => 1]);
        $container = $this->mockContainer();
        $container->setParameter('kernel.bundles', []);
        System::setContainer($container);
        $this->assertCount(1, $util->findModulePages($module, false, false));
        $container->setParameter('kernel.bundles', ['blocks' => 'blocks']);
        System::setContainer($container);
        $this->assertCount(1, $util->findModulePages($module, false, false));

        $this->assertSame(
            $util->findModulePages($module, false, false),
            $util->findModulePages($module, false, true)
        );

        $module = $this->mockClassWithProperties(ModuleModel::class, ['id' => 2]);
        $container->setParameter('kernel.bundles', []);
        System::setContainer($container);
        $this->assertCount(2, $util->findModulePages($module, false, false));
        $container->setParameter('kernel.bundles', ['blocks' => 'blocks']);
        System::setContainer($container);
        $this->count = 0;
        $this->assertCount(4, $util->findModulePages($module, false, false));
        $this->count = 0;
        $this->assertCount(4, $util->findModulePages($module, true, false));
        $this->count = 0;
        $this->assertCount(4, $util->findModulePages($module, true, true));
    }

    public function prepareFramework()
    {
        $modelAdapter = $this->mockAdapter([
            'getClassFromTable',
        ]);
        $modelAdapter->method('getClassFromTable')->with($this->anything())->willReturnCallback(function ($table) {
            switch ($table) {
                case 'tl_content':
                    return ContentModel::class;

                case 'tl_null_class':
                    return 'Huh\Null\Class\Nullclass';

                case 'tl_cfg_tag':
                    return CfgTagModel::class;

                case 'null':
                    return null;

                default:
                    return null;
            }
        });

        $contentModel = $this->mockClassWithProperties(ContentModel::class, [
            'id' => 5,
            'alias' => 'alias',
            'pid' => 3,
        ]);
        $contentModelAdapter = $this->createContentModelAdapter($contentModel);

        $pageModelAdapter = $this->mockAdapter(['findMultipleByIds']);
        $pageModelAdapter->method('findMultipleByIds')->willReturnArgument(0);

        $framework = $this->mockContaoFramework([
            Model::class => $modelAdapter,
            ContentModel::class => $contentModelAdapter,
            CfgTagModel::class => null,
            PageModel::class => $pageModelAdapter,
        ]);

        $framework->method('createInstance')->willReturnCallback(function ($classType) {
            switch ($classType) {
                case Database::class:
                    $db = $this->getMockBuilder(Database::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['prepare', 'execute'])
                        ->getMock();
                    $db->method('prepare')->willReturnSelf();
                    $db->method('execute')->willReturnCallback(function ($id) {
                        $result = $this->createMock(Database\Result::class);

                        switch ($id) {
                            case 0:
                            default:
                                $result->method('count')->willReturn(0);

                                break;

                            case 1:
                                $result->method('count')->willReturn(1);
                                $result->method('fetchEach')->willReturn([1]);

                                break;

                            case 2:
                                if ($this->count > 0) {
                                    $result->method('count')->willReturn(2);
                                    $result->method('fetchEach')->willReturn([4, 5]);
                                } else {
                                    $result->method('count')->willReturn(2);
                                    $result->method('fetchEach')->willReturn([1, 2]);
                                }

                                break;
                        }
                        ++$this->count;

                        return $result;
                    });

                    return $db;

                    break;
            }
        });

        return $framework;
    }

    public function createContentModelAdapter($contentModel)
    {
        $contentModelAdapter = $this->mockAdapter([
            'findByPk',
            'findBy',
            'findOneBy',
        ]);
        $contentModelAdapter->method('findByPk')->with($this->anything(), $this->anything())->willReturnCallback(function ($pk, $option) use ($contentModel) {
            switch ($pk) {
                case 'alias':
                    return $contentModel;

                case 5:
                    return $contentModel;

                default:
                    return null;
            }
        });
        $contentModelAdapter->method('findBy')->with($this->anything(), $this->anything(), $this->anything())->willReturnCallback(function ($columns, $values, $options = []) use ($contentModel) {
            if ('id' === $columns[0] && 5 === $values[0]) {
                return $contentModel;
            }

            if ('pid' === $columns[0] && 3 === $values[0]) {
                $collection = new Model\Collection([$contentModel], 'tl_content');

                return $collection;
            }

            return null;
        });

        $model = $this->createMock(ContentModel::class);
        $contentModelAdapter->method('findOneBy')->willReturn($model);

        return $contentModelAdapter;
    }

    /**
     * @return \Contao\Model | \PHPUnit_Framework_MockObject_MockObject
     */
    public function getModel($idNull = false)
    {
        if ($idNull) {
            return $this->mockClassWithProperties(Model::class, ['id' => null]);
        }

        return $this->mockClassWithProperties(Model::class, ['id' => 5]);
    }

    protected function createModelUtilMock()
    {
        $containerUtilMock = $this->createMock(ContainerUtil::class);
        $framwork = $this->prepareFramework();

        return new ModelUtil($framwork, $containerUtilMock);
    }
}
