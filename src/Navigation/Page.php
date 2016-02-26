<?php

namespace SleepingOwl\Admin\Navigation;

use Gate;
use SleepingOwl\Admin\Navigation;
use Illuminate\Routing\UrlGenerator;
use SleepingOwl\Admin\Model\ModelConfiguration;

class Page extends Navigation
{

    /**
     * Menu item related model class
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $title;

    /**
     * Menu item icon
     * @var string
     */
    protected $icon;

    /**
     * Menu item url
     * @var string
     */
    protected $url;

    /**
     * @var int
     */
    protected $priority = 100;

    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @var Page
     */
    protected $parent;

    /**
     * @param string|null $modelClass
     */
    public function __construct($modelClass = null)
    {
        $this->setModel($modelClass);

        parent::__construct();
    }

    /**
     * @param string|array|Page|null $page
     *
     * @return Page
     */
    public function addPage($page = null)
    {
        $page = parent::addPage($page);
        $page->setParent($this);

        return $page;
    }

    /**
     * @param string $model
     *
     * @return $this
     */
    protected function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return ModelConfiguration
     */
    public function getModelConfiguration()
    {
        if (! $this->hasModel()) {
            return;
        }

        return app('sleeping_owl')->getModel($this->model);
    }

    /**
     * @return bool
     */
    public function hasModel()
    {
        return ! is_null($this->model) and class_exists($this->model);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if (is_null($this->title) and $this->hasModel()) {
            return $this->getModelConfiguration()->getTitle();
        }

        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = "<i class=\"{$icon}\"></i>";

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if (is_null($this->url) and $this->hasModel()) {
            return $this->getModelConfiguration()->getDisplayUrl();
        }

        if (strpos($this->url, '://') !== false) {
            return $this->url;
        }

        if (is_string($this->url)) {
            $this->url = url($this->url);
        }

        if ($this->url instanceof UrlGenerator) {
            return $this->url->full();
        }

        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return $this
     */
    public function setActive()
    {
        $this->active = true;

        if (! is_null($this->getParent())) {
            $this->getParent()->setActive();
        }

        return $this;
    }

    /**
     * @param string $title
     *
     * @return Page|false
     */
    public function findPageByTitle($title)
    {
        if ($this->getTitle() == $title) {
            return $this;
        }

        return parent::findPageByTitle($title);
    }

    /**
     * @param Page $page
     *
     * @return $this
     */
    public function setParent(Page $page)
    {
        $this->parent = $page;

        return $this;
    }

    /**
     * @return Page
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return Closure
     */
    public function getAccessLogic()
    {
        if (! is_callable($this->accessLogic)) {
            if ($this->hasModel()) {
                return function () {
                    return $this->getModelConfiguration()->isDisplayable();
                };
            }

            if (! is_null($parent = $this->getParent())) {
                return $parent->getAccessLogic();
            }
        }

        return parent::getAccessLogic();
    }

    /**
     * @return bool
     */
    public function checkAccess()
    {
        $accessLogic = $this->getAccessLogic();

        if (is_callable($accessLogic)) {
            return call_user_func($accessLogic, $this);
        }

        return $accessLogic;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'pages'    => parent::toArray(),
            'hasChild' => $this->hasChild(),
            'title'    => $this->getTitle(),
            'icon'     => $this->getIcon(),
            'priority' => $this->getPriority(),
            'url'      => $this->getUrl(),
            'isActive' => $this->isActive()
        ];
    }

    /**
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function render()
    {
        return app('sleeping_owl.template')->view('_partials.navigation.page', $this->toArray());
    }
}