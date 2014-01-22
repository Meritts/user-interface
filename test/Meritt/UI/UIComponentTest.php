<?php

namespace Meritt\UI;

use Meritt\Gimme\Packages\StaticPackage;
use org\bovigo\vfs\vfsStream;
use Meritt\Gimme\Configuration\PackageConfiguration;
use Meritt\Gimme\Metadata\PackageMetadata;
use Meritt\Gimme\Configuration\Configuration;
use Meritt\Gimme\Metadata\BuildInformation;

class UIComponentTest extends \PHPUnit_Framework_TestCase
{
    protected $root;

    protected $manager;

    /**
     * @var UIComponent
     */
    protected $component;

    protected function setUp()
    {
        $this->root = vfsStream::setup(
            'testing',
            null,
            [
                'TestComponent.phtml' => '<h1>Hello World</h1>'
            ]
        );

        if (!$this->component) {
            set_include_path(
                get_include_path() . PATH_SEPARATOR . vfsStream::url('testing')
            );
        }

        $this->manager = $this->getMock(
            'Meritt\Gimme\PackageManager',
            [],
            [],
            '',
            false
        );

        $this->component = $this->getMock(
            'Meritt\UI\UIComponent',
            ['getBaseUrl', 'configureRequirements', 'configure'],
            [],
            'TestComponent'
        );

        $this->component->expects($this->any())
                        ->method('getBaseUrl')
                        ->will($this->returnValue('/testing/'));

        UIComponent::setManager($this->manager);
    }

    /**
     * @test
     */
    public function toStringMustIncludeTheComponentAssets()
    {
        $config = new Configuration();
        $config->setSavedPackagesDir(vfsStream::url('testing'));
        $config->setBaseUri('gimme');

        $pkg1 = new StaticPackage(
            $config->newPackageConfiguration('js/test.js', []),
            new PackageMetadata(
                1,
                'application/javascript',
                new BuildInformation(1, vfsStream::url('testing/gimme/1/js/test.js'), 1)
            )
        );

        $pkg2 = new StaticPackage(
            $config->newPackageConfiguration('css/test.css', []),
            new PackageMetadata(
                1,
                'text/css',
                new BuildInformation(1, vfsStream::url('testing/gimme/1/css/test.css'), 1)
            )
        );

        $pkg3 = new StaticPackage(
            $config->newPackageConfiguration('js/test2.js', []),
            new PackageMetadata(
                1,
                'application/javascript',
                new BuildInformation(1, vfsStream::url('testing/gimme/1/js/test2.js'), 1)
            )
        );

        $this->manager->expects($this->at(0))
                      ->method('get')
                      ->with('Meritt\Test\Assets\Js\test.coffee')
                      ->will($this->returnValue($pkg1));

        $this->manager->expects($this->at(1))
                      ->method('get')
                      ->with('Meritt\Test\Assets\Css\test.less')
                      ->will($this->returnValue($pkg2));

        $this->manager->expects($this->at(2))
                      ->method('get')
                      ->with('Meritt\Test\Assets\Js\test2.coffee')
                      ->will($this->returnValue($pkg3));


        $this->component->requires('Meritt\Test\Assets\Js\test.coffee');
        $this->component->requires('Meritt\Test\Assets\Css\test.less');
        $this->component->calls('Meritt\Test\Assets\Js\test2.coffee', 'test');

        $out = '<link rel="stylesheet" type="text/css" href="/testing/gimme/1/css/test.css">'
               . '<h1>Hello World</h1>'
               . '<script type="text/javascript" src="/testing/gimme/1/js/test.js"></script>'
               . '<script type="text/javascript" src="/testing/gimme/1/js/test2.js"></script>'
               . '<script type="text/javascript">'
               . 'require(["mcc","test"], function(mcc){ mcc.init_behaviors({"test":[[]]}); });</script>';

        $this->assertEquals($out, $this->component->__toString());
    }

    /**
     * @test
     */
    public function configureRequirementsMustBeCalledBeforeShow()
    {
        $this->component->expects($this->once())
                        ->method('configureRequirements');

        $this->component->__toString();
    }

    /**
     * @test
     */
    public function configureMustBeCalledBeforeShow()
    {
        $this->component->expects($this->once())
                        ->method('configure');

        $this->component->__toString();
    }
}
