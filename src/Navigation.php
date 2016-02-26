<?php

namespace SleepingOwl\Admin;

use Closure;
use Illuminate\Support\Collection;
use SleepingOwl\Admin\Navigation\Page;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;

class Navigation implements Renderable, Arrayable
{
    /**
     * @var Collection
     */
    protected $items;

    /**
     * @var Closure
     */
    protected $accessLogic;

    public function __construct()
    {
        $this->items = new Collection();
    }

    /**
     * @param array $navigation
     */
    public function setFromArray(array $navigation)
    {
        foreach ($navigation as $page) {
            $this->addPage($page);
        }
    }

    /**
     * @param string|array|Page|null $page
     *
     * @return Page
     */
    public function addPage($page = null)
    {
        if (is_array($page)) {
            $page = $this->createPageFromArray($page);
        } else if (is_string($page) or is_null($page)) {
            $page = new Page($page);
        }

        if (! ($page instanceof Page)) {
            return;
        }

        $this->getPages()->push($page);

        return $page;
    }

    /**
     * @return Collection
     */
    public function getPages()
    {
        return $this->items;
    }

    /**
     * @param Closure $callback
     *
     * @return $this
     */
    public function setItems(Closure $callback)
    {
        call_user_func($callback, $this);

        return $this;
    }

    /**
     * @param Closure $accessLogic
     *
     * @return $this
     */
    public function setAccessLogic(Closure $accessLogic)
    {
        $this->accessLogic = $accessLogic;

        return $this;
    }

    /**
     * @return Closure
     */
    public function getAccessLogic()
    {
        return is_callable($this->accessLogic) ? $this->accessLogic : true;
    }

    /**
     * @return bool
     */
    public function hasChild()
    {
        return $this->getPages()->count() > 0;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getPages();
    }

    /**
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function render()
    {
        $this->findActive();
        $this->filterByAccessRights();
        $this->sort();

        return app('sleeping_owl.template')->view('_partials.navigation.navigation', [
            'pages' => $this->toArray()
        ])->render();
    }

    public function filterByAccessRights()
    {
        $this->items = $this->getPages()->filter(function (Page $page) {
            $page->filterByAccessRights();

            return $page->checkAccess();
        });
    }

    public function sort()
    {
        $this->items = $this->getPages()->sortBy(function (Page $page) {
            $page->sort();

            return $page->getPriority();
        });
    }

    protected function findActive()
    {
        $this->getPages()->each(function (Page $page) {
            if ($page->getUrl() == url()->current()) {
                $page->setActive();
            }

            $page->findActive();
        });
    }

    /**
     * @param string $title
     *
     * @return Page|false
     */
    public function findPageByTitle($title)
    {
        foreach ($this->getPages() as $page) {
            if ($page->findPageByTitle($title)) {
                return $page;
            }
        }

        return false;
    }

    /**
     * @param array $data
     *
     * @return Page
     */
    protected function createPageFromArray(array $data)
    {
        $page = new Page();

        foreach ($data as $key => $value) {
            if (method_exists($page, $method = 'set'.ucfirst($key))) {
                $page->{$method}($value);
            }
        }

        if (isset($data['pages']) and is_array($data['pages'])) {
            foreach ($data['pages'] as $child) {
                $page->addPage($child);
            }
        }

        return $page;
    }
}