<?php

namespace Meritt\UI;

use Meritt\Gimme\Packages\StaticPackage;
use org\bovigo\vfs\vfsStream;

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
            ['getBaseUrl'],
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
        $pkg1 = new StaticPackage('js/test.js', 'application/javascript');
        $pkg2 = new StaticPackage('css/test.css', 'text/css');
        $pkg3 = new StaticPackage('js/test2.js', 'application/javascript');

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

        $text = '<link rel="stylesheet" type="text/css" href="/testing/css/test.css">'
                . '<h1>Hello World</h1>'
                . '<script type="application/javascript" src="/testing/js/test.js"></script>'
                . '<script type="application/javascript" src="/testing/js/test2.js"></script>'
                . '<script type="application/javascript">mcc.init_behaviors({"test":[[]]});</script>';

        $this->assertEquals($text, $this->component->__toString());
    }
}
