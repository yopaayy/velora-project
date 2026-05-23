<?php

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            match (true) {
                method_exists($this, 'filter' . ucfirst($field)) => $this->{'filter' . ucfirst($field)}($query, $value),
                str_ends_with($field, '_from') => $query->where(str_replace('_from', '', $field), '>=', $value),
                str_ends_with($field, '_to') => $query->where(str_replace('_to', '', $field), '<=', $value),
                $field === 'search' => $this->applySearch($query, $value),
                default => $query->where($field, $value),
            };
        }

        return $query;
    }

    protected function applySearch(Builder $query, string $value): void
    {
        if (property_exists($this, 'searchable') && !empty($this->searchable)) {
            $query->where(function ($q) use ($value) {
                foreach ($this->searchable as $i => $column) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->$method($column, 'LIKE', "%{$value}%");
                }
            });
        }
    }
}
