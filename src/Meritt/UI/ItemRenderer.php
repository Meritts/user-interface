<?php
namespace Meritt\UI;

abstract class ItemRenderer extends UIComponent
{
    /**
     * Item to render
     *
     * @var object
     */
    protected $item;

    /**
     * Renders an object
     *
     * @param object $item
     * @return ItemRenderer
     */
    public function render($item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Returns the item
     *
     * @return object
     */
    public function getItem()
    {
        return $this->item;
    }
}
