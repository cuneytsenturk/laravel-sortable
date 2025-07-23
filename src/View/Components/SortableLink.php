<?php

namespace Akaunting\Sortable\View\Components;

use Akaunting\Sortable\Support\SortableLink as Base;
use Illuminate\View\Component;

final class SortableLink extends Component
{
    /**
     * The sortablelink column.
     */
    public string $column;

    /**
     * The sortablelink title.
     */
    public string $title;

    /**
     * The sortablelink query.
     */
    public array $query;

    /**
     * The sortablelink arguments.
     */
    public array $arguments;

    /**
     * Create the component instance.
     */
    public function __construct(string $column, string $title, array $query = [], array $arguments = [])
    {
        $this->column = $column;
        $this->title = $title;
        $this->query = $query;
        $this->arguments = $arguments;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): string
    {
        return Base::render([
            $this->column,
            $this->title,
            $this->query,
            $this->arguments,
        ]);
    }
}
