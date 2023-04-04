<?php

namespace Addons\Core\Models;
use Addons\Core\ApiTrait;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ApiHelper {
    use ApiTrait;

    const perPage = 50;

    private Builder $builder;
    private int $page = 0;
    private int $size = 0;

    private array $condition = [];


    public static function make(Builder $builder, Request $request): Api {
        return new Api($builder, $request);
    }

    public function __construct(Builder $builder, Request $request) {
        $this->builder = $builder;
        $this->filters($request->input('f'));
        $this->orders($request->input('o'));
        $this->queries($request->input('q'));
        $this->page($request->input('page') ?? 1);
        $this->size($request->input('size') ?? static::perPage);

        if (boolval($request->input('all'))) {
            $this->size(10000);
        }
    }

    public function page(int $page): static {
        $this->condition['page'] = $page <= 0 ? 1 : $page;
        return $this;
    }

    public function size(int $size): static {
        $this->condition['size'] = $size <=0 ? static::perPage : $size;
        return $this;
    }

    public function builder(Builder $builder): static {
        $this->builder = $builder;
        return $this;
    }

    public function filter(string $field, string $condition, mixed $value): static {
        if (!isset($this->condition['f'][$field]))
            $this->condition['f'][$field] = [];
        $this->condition['f'][$field][$condition] = $value;
        return $this;
    }

    public function query(string $field, mixed $value = null): static {
        $this->condition['q'][$field] = $value;
        return $this;
    }

    public function order(string $field, string $ascDesc = 'asc'): static {
        $this->condition['o'][$field] = $ascDesc;
        return $this;
    }

    public function filters(?array $filters): static {
        $this->condition['f'] = $filters ?? [];
        return $this;
    }

    public function orders(?array $orders): static {
        $this->condition['o'] = $orders ?? [];
        return $this;
    }

    public function queries(?array $queries): static {
        $this->condition['q'] = $queries ?? [];
        return $this;
    }

    public function count(): int {
        return $this->_getCount(collect($this->condition), $this->builder, false);
    }

    public function data(callable $callback = null, array $columns = ['*']): array|null {
        return $this->_getData(collect($this->condition), $this->builder, $callback, $columns);
    }

    public function export(callable $callback = null, array $columns = ['*']): array|null {
        return $this->_getExport(collect($this->condition), $this->builder, $callback, $columns);
    }

    public function datable(callable $callback = null, array $columns = ['*']): array|null {
        $total = $this->count();
        $data = $this->data($callback, $columns);
        $data['recordsTotal'] = $total; //不带 f q 条件的总数
        $data['recordsFiltered'] = $data['filter']; //带 f q 条件的总数
        return $data;
    }

}