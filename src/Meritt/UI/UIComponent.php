<?php

namespace Meritt\UI;

use Lcobucci\ActionMapper2\Http\Request;
use Meritt\Gimme\Package;
use Meritt\Gimme\Receiver as ReceiverInterface;
use Meritt\Gimme\UserInterface\Receiver;
use Meritt\Gimme\UserInterface\ReceiverHook;

abstract class UIComponent extends \Lcobucci\DisplayObjects\Core\UIComponent implements
    ReceiverInterface
{
    use Receiver;

    /**
     * The HTTP request
     *
     * @var Request
     */
    protected static $request;

    /**
     * Configures the HTTP request
     *
     * @param Request $request
     */
    public static function setRequest(Request $request)
    {
        static::$request = $request;
    }

    /**
     * Returns the HTTP request
     *
     * @return Request
     */
    protected function getRequest()
    {
        return static::$request;
    }

    /**
     * Returns the string representation of the object
     *
     * @param string $class
     * @return string
     */
    public function show($class = null)
    {
        $this->configureRequirements();

        $content = parent::show($class);

        return $this->getStyleTags()
               . $content
               . $this->getScriptTags();
    }

    /**
     * Configures the assets and behaviors that the component requires (should be overwrite when needed)
     */
    protected function configureRequirements()
    {
    }

    /**
     * @return string
     */
    protected function getStyleTags()
    {
        $content = '';

        foreach ($this->loadDependencies('text/css', true) as $dep) {
            $content .= $this->createPackageTag($dep);
        }

        return $content;
    }

    /**
     * @param bool $appendHooks
     * @return string
     */
    protected function getScriptTags($appendHooks = true)
    {
        $content = '';

        foreach ($this->loadDependencies('application/javascript', true) as $dep) {
            $content .= $this->createPackageTag($dep);
        }

        if (!$appendHooks || !isset($this->hooks[0])) {
            return $content;
        }

        $content .= $this->createHookTag();

        return $content;
    }

    /**
     * @param Package $package
     * @return string
     */
    protected function createPackageTag(Package $package)
    {
        if ($package->getMimeType() == 'application/javascript') {
            return sprintf(
                '<script type="application/javascript" src="%s"></script>',
                $this->getUrl($package->getUri())
            );
        }

        if ($package->getMimeType() == 'text/css') {
            return sprintf(
                '<link rel="stylesheet" type="text/css" href="%s">',
                $this->getUrl($package->getUri())
            );
        }
    }

    /**
     * @param ReceiverHook $hook
     * @return string
     */
    protected function createHookTag()
    {
        $map = [];

        foreach ($this->hooks as $hook) {
            if (!isset($map[$hook->getBehavior()])) {
                $map[$hook->getBehavior()] = [];
            }

            $map[$hook->getBehavior()][] = $hook->getParameters();
        }

        return sprintf(
            '<script type="application/javascript">mcc.init_behaviors(%s);</script>',
            json_encode($map)
        );
    }
}
