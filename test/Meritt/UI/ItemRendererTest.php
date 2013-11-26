<?php

namespace Meritt\UI;

class ItemRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function renderShouldReturnSelf()
    {
        $renderer = $this->getMockForAbstractClass(ItemRenderer::class);

        $item = new \stdClass();
        $item->testProperty = 'Han Solo';

        $this->assertSame($renderer, $renderer->render($item));

        return $renderer;
    }

    /**
     * @test
     * @depends renderShouldReturnSelf
     */
    public function renderShouldDefineAnInternalItem(ItemRenderer $renderer)
    {
        $this->assertEquals('Han Solo', $renderer->getItem()->testProperty);
    }
}
