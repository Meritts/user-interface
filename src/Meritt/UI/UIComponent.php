<?php

namespace Meritt\UI;

use Lcobucci\ActionMapper2\Http\Request;
use Lcobucci\DisplayObjects\Core\UIComponent as Component;
use Lcobucci\DisplayObjects\Core\UIComponentNotFoundException;
use Meritt\Gimme\Package;
use Meritt\Gimme\Receiver as ReceiverInterface;
use Meritt\Gimme\UserInterface\Receiver;
use Meritt\Gimme\UserInterface\ReceiverHook;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class UIComponent extends Component implements ReceiverInterface
{
    use Receiver {
        requires as baseRequires;
        calls as baseCalls;
    }

    /**
     * The HTTP request
     *
     * @var Request
     */
    protected static $request;

    /**
     * The list of child components
     *
     * @var UIComponent[]
     */
    protected $childNodes;

    /**
     * The parent component
     *
     * @var UIComponent
     */
    protected $parentNode;

    /**
     * @var array
     */
    protected $renderedChildren;

    /**
     * @var boolean
     */
    protected $configured;

    /**
     * @var ParameterBag
     */
    protected static $options;

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
     * Class constructor
     */
    public function __construct()
    {
        $this->childNodes = [];
        $this->renderedChildren = [];
        $this->configured = false;
    }

    /**
     * @param UIComponent $parentNode
     */
    public function setParentNode(UIComponent $parentNode)
    {
        $this->parentNode = $parentNode;
    }

    /**
     * @param Component $component
     */
    public function appendChild(Component $component)
    {
        $this->childNodes[] = $component;

        if ($component instanceof UIComponent) {
            $component->setParentNode($this);
        }
    }

    /**
     * @param Component $component
     */
    public function prependChild(Component $component)
    {
        array_unshift($this->childNodes, $component);

        if ($component instanceof UIComponent) {
            $component->setParentNode($this);
        }
    }

    /**
     * @param string $index
     * @return string
     */
    public function renderChild($index)
    {
        if (!isset($this->childNodes[$index])) {
            return '';
        }

        return $this->renderComponent($this->childNodes[$index]);
    }

    /**
     * Renders the given component
     *
     * @param Component $component
     * @return string
     */
    protected function renderComponent(Component $component)
    {
        $hash = spl_object_hash($component);

        if (in_array($hash, $this->renderedChildren, true)) {
            return '';
        }

        $this->renderedChildren[] = $hash;

        return (string) $component;
    }

    /**
     * {@inheritdoc}
     */
    public function requires($resource)
    {
        if ($this->parentNode) {
            return $this->parentNode->requires($resource);
        }

        return $this->baseRequires($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function calls($resource, $behavior, array $parameters = array())
    {
        if ($this->parentNode) {
            return $this->parentNode->calls($resource, $behavior, $parameters);
        }

        return $this->baseCalls($resource, $behavior, $parameters);
    }

    /**
     * @return string
     */
    protected function getChildrenContent()
    {
        $content = '';

        array_walk(
            $this->childNodes,
            function (Component $child) use (&$content) {
                $content .= $this->renderComponent($child);
            }
        );

        return $content;
    }

    /**
     * Returns the string representation of the object
     *
     * @param string $class
     * @return string
     */
    public function show($class = null)
    {
        $this->configureComponent($this);

        $content = $this->getComponentContent($class);

        return $this->getStyleTags()
               . $content
               . $this->getScriptTags();
    }

    /**
     * Retrieves the component content
     *
     * @param string $class
     * @return string
     */
    protected function getComponentContent($class = null)
    {
        try {
            return parent::show($class);
        } catch (UIComponentNotFoundException $exception) {
            return $this->getChildrenContent();
        }
    }

    /**
     * Configures the children components
     */
    protected function configureChildren()
    {
        foreach ($this->childNodes as $child) {
            $this->configureComponent($child);
        }
    }

    /**
     * Configures the given component
     *
     * @param Component $component
     */
    protected function configureComponent(Component $component)
    {
        if (!$component instanceof UIComponent || $component->configured) {
            return ;
        }

        $component->configure();
        $component->configureRequirements();
        $component->configureChildren();

        $component->configured = true;
    }

    /**
     * Configures the component children and other information
     */
    protected function configure()
    {
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
                '<script type="text/javascript" src="%s"></script>',
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
     * @return string
     */
    protected function createHookTag()
    {
        $map = [];

        /** @var $hook ReceiverHook */
        foreach ($this->hooks as $hook) {
            if (!isset($map[$hook->getBehavior()])) {
                $map[$hook->getBehavior()] = [];
            }

            $map[$hook->getBehavior()][] = $hook->getParameters();
        }

        $hookList = array_keys($map);
        array_unshift($hookList, 'mcc');

        return sprintf(
            '<script type="text/javascript">require(%s, function(mcc){ mcc.init_behaviors(%s); });</script>',
            json_encode($hookList),
            json_encode($map)
        );
    }

    /**
     * @param ParameterBag $options
     */
    public static function setOptions(ParameterBag $options)
    {
        static::$options = $options;
    }

    /**
     * @return ParameterBag
     */
    public static function getOptions()
    {
        return static::$options;
    }
}
